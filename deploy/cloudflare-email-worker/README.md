# Cloudflare Email Worker – příjem pošty

Přeposílá e-maily z `servis@konzolak.com` do servisního systému včetně příloh.

## Deploy přes dashboard (bez Node)

1. Cloudflare → **Workers & Pages** → otevři worker, který zpracovává poštu
   (Email Routing → Routing rules ukazuje jeho jméno).
2. **Edit code** → smaž obsah, vlož `worker.js` z této složky → **Deploy**.
3. **Settings → Variables and Secrets**:
   - Secret `POSTA_TOKEN` = stejná hodnota jako `POSTA_TOKEN` v `.env` na serveru.
   - (volitelně) Variable `FORWARD_TO` = e-mail, kam chodit kopii (Gmail).

Pokud editor odmítne `import PostalMime from "postal-mime"`, použij wrangler:

```bash
npm i -g wrangler
wrangler login
cd deploy/cloudflare-email-worker
npm init -y && npm i postal-mime
wrangler deploy
```

(k tomu je potřeba `wrangler.toml` s `name`, `main = "worker.js"`, `compatibility_date`
a `send_email`/`email` triggerem – doplň podle stávajícího workeru).

## Payload, který worker posílá

`POST https://servis.konzolak.com/api/posta/prijem`
hlavička `X-Posta-Token: <POSTA_TOKEN>`

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
  "attachments": [
    { "filename": "faktura.pdf", "mimeType": "application/pdf", "content": "<base64>" }
  ]
}
```

Server přílohy dekóduje, uloží do `storage/app/private/posta/{zprava_id}/`
a ve vlákně zprávy je nabídne ke stažení.
