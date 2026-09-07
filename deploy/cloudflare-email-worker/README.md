# Cloudflare Email Worker – `posta-servis`

Přeposílá e-maily z `servis@konzolak.com` do servisního systému **včetně příloh**
a zároveň nechává kopii chodit na záložní Gmail.

## Nasazení (dashboard, bez Node)

1. Cloudflare → **Workers & Pages** → **posta-servis** → **Edit code**.
2. Smaž obsah, vlož `worker.js` z této složky → **Deploy**.
3. Proměnné už jsou nastavené a nemění se:
   - `INGEST_TOKEN` (secret) = `POSTA_TOKEN` z `.env` na serveru
   - `INGEST_URL` = `https://servis.konzolak.com/api/posta/prijem`
   - `BACKUP_EMAIL` = záložní Gmail

Kód nemá žádné npm závislosti – MIME se parsuje ručně, takže projde přímo
přes dashboard editor.

## Co worker posílá

`POST {INGEST_URL}` s hlavičkou `X-Posta-Token: {INGEST_TOKEN}`:

```jsonc
{
  "from": "odesilatel@example.com",
  "fromName": "Jméno",
  "to": "servis@konzolak.com",
  "subject": "…",
  "text": "…",
  "html": "…",
  "messageId": "<…>",
  "inReplyTo": "<…>",
  "references": "<…> <…>",
  "date": "Sat, 07 Sep 2026 09:11:42 +0000",
  "spam": false,
  "attachments": [
    { "filename": "faktura.pdf", "mimeType": "application/pdf", "content": "<base64>" }
  ]
}
```

## Omezení

- Přílohy se berou jen z MIME částí kódovaných `base64` (což je u e-mailů standard).
  Části v `8bit`/`binary` se přeskočí.
- Strop na přílohy: ~18 MB base64 v jednom e-mailu (server bere do 25 MB / 30 MB post).
- Server přílohy uloží do `storage/app/private/posta/{zprava_id}/` a nabídne ke
  stažení ve vlákně zprávy.
