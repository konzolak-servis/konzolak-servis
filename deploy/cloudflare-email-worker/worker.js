/**
 * Cloudflare Email Worker – příjem pošty pro servis@konzolak.com
 * ---------------------------------------------------------------
 * Přijme e-mail přes Cloudflare Email Routing, rozparsuje ho (včetně příloh)
 * a pošle JSON na servisní systém (POST /api/posta/prijem).
 *
 * Nastavení Workeru:
 *   - Secret  POSTA_TOKEN  … stejná hodnota jako POSTA_TOKEN v .env na serveru
 *   - (volitelně) Var  FORWARD_TO  … kam přeposlat kopii e-mailu (např. Gmail)
 *
 * Závislost: postal-mime  (npm) – dashboard editor si ji při Deploy zabalí sám.
 */
import PostalMime from "postal-mime";

const ENDPOINT = "https://servis.konzolak.com/api/posta/prijem";

// Strop na celkovou velikost příloh v jednom e-mailu (server bere do ~25 MB).
const MAX_ATTACHMENTS_BYTES = 15 * 1024 * 1024;

export default {
  async email(message, env, ctx) {
    let payload;

    try {
      const raw = new Uint8Array(await new Response(message.raw).arrayBuffer());
      const email = await PostalMime.parse(raw);

      const attachments = [];
      let total = 0;

      for (const a of email.attachments || []) {
        const bytes =
          a.content instanceof ArrayBuffer
            ? new Uint8Array(a.content)
            : typeof a.content === "string"
            ? new TextEncoder().encode(a.content)
            : a.content;

        if (!bytes || !bytes.length) continue;
        total += bytes.length;
        if (total > MAX_ATTACHMENTS_BYTES) break;

        attachments.push({
          filename: a.filename || "priloha",
          mimeType: a.mimeType || "application/octet-stream",
          content: bytesToBase64(bytes),
        });
      }

      const hdr = (name) => {
        const h = (email.headers || []).find(
          (x) => (x.key || "").toLowerCase() === name
        );
        return h ? h.value : null;
      };

      payload = {
        from: (message.from || email.from?.address || "").toLowerCase(),
        fromName: email.from?.name || null,
        to: message.to || null,
        subject: email.subject || "",
        text: email.text || "",
        html: email.html || "",
        messageId: email.messageId || hdr("message-id"),
        inReplyTo: email.inReplyTo || hdr("in-reply-to"),
        references: email.references || hdr("references"),
        date: email.date || hdr("date"),
        attachments,
      };
    } catch (err) {
      // I kdyby parsování selhalo, ať se aspoň uloží holá zpráva.
      payload = {
        from: (message.from || "").toLowerCase(),
        to: message.to || null,
        subject: "(e-mail se nepodařilo rozparsovat)",
        text: String(err && err.stack ? err.stack : err),
        attachments: [],
      };
    }

    try {
      const res = await fetch(ENDPOINT, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Posta-Token": env.POSTA_TOKEN,
        },
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        console.log("posta/prijem HTTP " + res.status + ": " + (await res.text()));
      }
    } catch (err) {
      console.log("posta/prijem fetch selhal: " + err);
    }

    // Volitelně: přeposlat kopii na Gmail, ať máš i běžnou schránku.
    if (env.FORWARD_TO) {
      try {
        await message.forward(env.FORWARD_TO);
      } catch (_) {}
    }
  },
};

function bytesToBase64(bytes) {
  let bin = "";
  const CHUNK = 0x8000;
  for (let i = 0; i < bytes.length; i += CHUNK) {
    bin += String.fromCharCode.apply(null, bytes.subarray(i, i + CHUNK));
  }
  return btoa(bin);
}
