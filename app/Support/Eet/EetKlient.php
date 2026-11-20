<?php

namespace App\Support\Eet;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Klient EET 2.0 – odeslání datové zprávy evidované tržby (SOAP 1.1, WS-Security, RSA-SHA256).
 * Podle „Formát a struktura údajů o evidované tržbě a popis datového rozhraní" v1.2a a vzorové zprávy GFŘ.
 */
class EetKlient
{
    public const URL = [
        'playground' => 'https://pg.trzbyeet.gov.cz/eet/services/EETServiceSOAP/v4',
        'produkce' => 'https://trzbyeet.gov.cz/eet/services/EETServiceSOAP/v4',
    ];

    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const NS_EET = 'http://fs.gov.cz/eet/schema/v4';
    private const NS_WSSE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private const NS_WSU = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    private const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';
    private const NS_EC = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    private const X509 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3';
    private const B64 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

    /** @var array{cert: string, pkey: string} */
    private array $klic;

    public function __construct(string $p12, string $heslo, private string $prostredi = 'playground', private int $timeout = 5)
    {
        $this->klic = self::nactiKlic($p12, $heslo);
    }

    /**
     * Načte certifikát a klíč z PKCS#12. Certifikáty CA EET bývají šifrované starším RC2-40, které OpenSSL 3
     * v PHP neotevře – pak se použije nástroj openssl s volbou -legacy (heslo jde přes proměnnou prostředí).
     *
     * @return array{cert: string, pkey: string}
     */
    public static function nactiKlic(string $p12, string $heslo): array
    {
        if (openssl_pkcs12_read($p12, $certs, $heslo)) {
            return ['cert' => $certs['cert'], 'pkey' => $certs['pkey']];
        }
        while (openssl_error_string()) {
            // vyprázdnit frontu chyb OpenSSL
        }

        $soubor = tempnam(sys_get_temp_dir(), 'eet');
        file_put_contents($soubor, $p12);
        try {
            $p = \Illuminate\Support\Facades\Process::env(['EETPASS' => $heslo])->timeout(15)->run([
                config('services.eet.openssl', 'openssl'), 'pkcs12', '-legacy', '-in', $soubor,
                '-passin', 'env:EETPASS', '-nodes',
            ]);
        } finally {
            @unlink($soubor);
        }

        $pem = $p->successful() ? $p->output() : '';
        preg_match('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', $pem, $c);
        preg_match('/-----BEGIN (?:RSA )?PRIVATE KEY-----.+?-----END (?:RSA )?PRIVATE KEY-----/s', $pem, $k);
        if (! $c || ! $k) {
            throw new RuntimeException('Pokladní certifikát nelze otevřít – zkontroluj soubor .p12 a heslo.');
        }

        return ['cert' => $c[0], 'pkey' => $k[0]];
    }

    /** EIČ z certifikátu (CN), např. CZ8551015704. */
    public function eicCertifikatu(): ?string
    {
        return openssl_x509_parse($this->klic['cert'])['subject']['CN'] ?? null;
    }

    public function platnostDo(): ?\Illuminate\Support\Carbon
    {
        $t = openssl_x509_parse($this->klic['cert'])['validTo_time_t'] ?? null;

        return $t ? \Illuminate\Support\Carbon::createFromTimestamp($t) : null;
    }

    /**
     * Sestaví a podepíše zprávu.
     *
     * @param  array{eic_popl: string, id_jednotky: int, id_pokl: string, porad_cis: string, dat_trzby: string, celk_trzba: float}  $data
     */
    public function zprava(array $data, bool $prvniZaslani = true, bool $overeni = false, ?string $uuid = null): array
    {
        $uuid ??= (string) Str::uuid();
        $d = new DOMDocument('1.0', 'UTF-8');
        $env = $d->createElementNS(self::NS_SOAP, 'soapenv:Envelope');
        $env->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:v4', self::NS_EET);
        $d->appendChild($env);

        $header = $d->createElementNS(self::NS_SOAP, 'soapenv:Header');
        $env->appendChild($header);
        $body = $d->createElementNS(self::NS_SOAP, 'soapenv:Body');
        $env->appendChild($body);
        $bodyId = 'id-' . strtoupper(bin2hex(random_bytes(12)));
        $body->setAttributeNS(self::NS_WSU, 'wsu:Id', $bodyId);

        $trzba = $d->createElementNS(self::NS_EET, 'v4:Trzba');
        $body->appendChild($trzba);
        $hl = $d->createElementNS(self::NS_EET, 'v4:Hlavicka');
        $hl->setAttribute('dat_odesl', self::cas(now()));
        $hl->setAttribute('overeni', $overeni ? 'true' : 'false');
        $hl->setAttribute('prvni_zaslani', $prvniZaslani ? 'true' : 'false');
        $hl->setAttribute('uuid_zpravy', $uuid);
        $trzba->appendChild($hl);
        $da = $d->createElementNS(self::NS_EET, 'v4:Data');
        $da->setAttribute('celk_trzba', self::castka($data['celk_trzba']));
        $da->setAttribute('dat_trzby', $data['dat_trzby']);
        $da->setAttribute('eic_popl', $data['eic_popl']);
        $da->setAttribute('id_jednotky', (string) $data['id_jednotky']);
        $da->setAttribute('id_pokl', $data['id_pokl']);
        $da->setAttribute('porad_cis', $data['porad_cis']);
        $trzba->appendChild($da);

        // WS-Security: certifikát + podpis těla (exc-c14n, SHA-256, RSA-SHA256)
        $sec = $d->createElementNS(self::NS_WSSE, 'wsse:Security');
        $sec->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsu', self::NS_WSU);
        $header->appendChild($sec);
        $x509Id = 'X509-' . strtoupper(bin2hex(random_bytes(12)));
        openssl_x509_export($this->klic['cert'], $pem);
        $bst = $d->createElementNS(self::NS_WSSE, 'wsse:BinarySecurityToken',
            preg_replace('/-----[^-]+-----|\s/', '', $pem));
        $bst->setAttribute('EncodingType', self::B64);
        $bst->setAttribute('ValueType', self::X509);
        $bst->setAttributeNS(self::NS_WSU, 'wsu:Id', $x509Id);
        $sec->appendChild($bst);

        $sig = $d->createElementNS(self::NS_DS, 'ds:Signature');
        $sig->setAttribute('Id', 'SIG-' . strtoupper(bin2hex(random_bytes(12))));
        $sec->appendChild($sig);
        $si = $d->createElementNS(self::NS_DS, 'ds:SignedInfo');
        $sig->appendChild($si);
        $cm = $d->createElementNS(self::NS_DS, 'ds:CanonicalizationMethod');
        $cm->setAttribute('Algorithm', self::NS_EC);
        $cm->appendChild($this->inclusive($d, 'soapenv v4'));
        $si->appendChild($cm);
        $sm = $d->createElementNS(self::NS_DS, 'ds:SignatureMethod');
        $sm->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $si->appendChild($sm);
        $ref = $d->createElementNS(self::NS_DS, 'ds:Reference');
        $ref->setAttribute('URI', '#' . $bodyId);
        $si->appendChild($ref);
        $tr = $d->createElementNS(self::NS_DS, 'ds:Transforms');
        $t = $d->createElementNS(self::NS_DS, 'ds:Transform');
        $t->setAttribute('Algorithm', self::NS_EC);
        $t->appendChild($this->inclusive($d, 'v4'));
        $tr->appendChild($t);
        $ref->appendChild($tr);
        $dm = $d->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $dm->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        $ref->appendChild($dm);
        $ref->appendChild($d->createElementNS(self::NS_DS, 'ds:DigestValue',
            base64_encode(hash('sha256', $body->C14N(true, false, null, ['v4']), true))));

        if (! openssl_sign($si->C14N(true, false, null, ['soapenv', 'v4']), $podpis, $this->klic['pkey'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Zprávu se nepodařilo podepsat.');
        }
        $sig->appendChild($d->createElementNS(self::NS_DS, 'ds:SignatureValue', base64_encode($podpis)));
        $ki = $d->createElementNS(self::NS_DS, 'ds:KeyInfo');
        $ki->setAttribute('Id', 'KI-' . strtoupper(bin2hex(random_bytes(12))));
        $str = $d->createElementNS(self::NS_WSSE, 'wsse:SecurityTokenReference');
        $str->setAttributeNS(self::NS_WSU, 'wsu:Id', 'STR-' . strtoupper(bin2hex(random_bytes(12))));
        $r = $d->createElementNS(self::NS_WSSE, 'wsse:Reference');
        $r->setAttribute('URI', '#' . $x509Id);
        $r->setAttribute('ValueType', self::X509);
        $str->appendChild($r);
        $ki->appendChild($str);
        $sig->appendChild($ki);

        return ['xml' => $d->saveXML($d->documentElement), 'uuid' => $uuid];
    }

    /**
     * Odešle tržbu. Vrací výsledek: ok (POK přijat), pok, chyba_kod/text, varovani, docasna (zkusit znovu), transakce_id.
     */
    public function odeslat(array $data, bool $prvniZaslani = true, bool $overeni = false): array
    {
        $z = $this->zprava($data, $prvniZaslani, $overeni);

        try {
            $resp = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'http://fs.gov.cz/eet/OdeslaniTrzby',
            ])->timeout($this->timeout)->connectTimeout(3)
                ->withBody($z['xml'], 'text/xml; charset=utf-8')
                ->post(self::URL[$this->prostredi]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'docasna' => true, 'chyba_kod' => null, 'chyba_text' => 'Bez spojení: ' . Str::limit($e->getMessage(), 150),
                'uuid' => $z['uuid'], 'pok' => null, 'varovani' => [], 'transakce_id' => null];
        }

        $vysledek = self::rozebrat($resp->body());

        return $vysledek + ['uuid' => $z['uuid'], 'transakce_id' => $resp->header('X-Global-Transaction-Id') ?: null,
            'http' => $resp->status()];
    }

    /** Rozbor odpovědi (Potvrzeni s POK, nebo Chyba s kódem; kód 0 = ověřovací režim OK). */
    public static function rozebrat(string $xml): array
    {
        $d = new DOMDocument();
        if (! @$d->loadXML($xml)) {
            return ['ok' => false, 'docasna' => true, 'chyba_kod' => null, 'chyba_text' => 'Neplatná odpověď serveru', 'pok' => null, 'varovani' => []];
        }
        $x = new DOMXPath($d);
        $x->registerNamespace('e', self::NS_EET);
        $varovani = [];
        foreach ($x->query('//e:Varovani') as $v) {
            $varovani[] = ['kod' => (int) $v->getAttribute('kod_varov'), 'text' => trim($v->textContent)];
        }
        if ($p = $x->query('//e:Potvrzeni')->item(0)) {
            return ['ok' => true, 'docasna' => false, 'pok' => $p->getAttribute('pok'), 'test' => $p->getAttribute('test') === 'true',
                'chyba_kod' => null, 'chyba_text' => null, 'varovani' => $varovani];
        }
        if ($c = $x->query('//e:Chyba')->item(0)) {
            $kod = (int) $c->getAttribute('kod');

            return ['ok' => $kod === 0, 'overeno' => $kod === 0, 'docasna' => $kod < 0, 'pok' => null,
                'chyba_kod' => $kod, 'chyba_text' => trim($c->textContent), 'varovani' => $varovani];
        }

        return ['ok' => false, 'docasna' => true, 'chyba_kod' => null, 'chyba_text' => 'Neznámá odpověď serveru', 'pok' => null, 'varovani' => []];
    }

    /** Čas ve formátu EET: místní čas s posunem, bez zlomků sekund (2027-01-09T04:25:28+01:00). */
    public static function cas(\DateTimeInterface $t): string
    {
        return \Illuminate\Support\Carbon::instance($t)->setTimezone('Europe/Prague')->format('Y-m-d\TH:i:sP');
    }

    /** Částka přesně na 2 desetinná místa, bez „-0.00". */
    public static function castka(float $v): string
    {
        $s = number_format(round($v, 2), 2, '.', '');

        return $s === '-0.00' ? '0.00' : $s;
    }

    private function inclusive(DOMDocument $d, string $prefixy): \DOMElement
    {
        $e = $d->createElementNS(self::NS_EC, 'ec:InclusiveNamespaces');
        $e->setAttribute('PrefixList', $prefixy);

        return $e;
    }
}
