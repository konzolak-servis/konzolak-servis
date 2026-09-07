/**
 * Cloudflare Email Worker – příjem pošty pro servis@konzolak.com
 * Nasazení: vlož tento kód do workeru "posta-servis" → Deploy.
 * Proměnné (Settings → Variables): INGEST_URL, INGEST_TOKEN, BACKUP_EMAIL
 *
 * Bez npm závislostí – MIME se parsuje ručně, přílohy se posílají jako base64
 * v poli `attachments`. Server je uloží a nabídne ke stažení u zprávy.
 */

export default {
  async email(message, env, ctx) {
    const INGEST_URL = env.INGEST_URL || "https://servis.konzolak.com/api/posta/prijem";
    const INGEST_TOKEN = env.INGEST_TOKEN || "";
    const BACKUP_EMAIL = env.BACKUP_EMAIL || "servis.konzoli.zlin@gmail.com";

    // strop na celkovou velikost příloh (v base64) v jednom e-mailu
    const MAX_ATTACH_B64 = 18 * 1024 * 1024;

    let raw = "";
    try { raw = await new Response(message.raw).text(); } catch (e) { raw = ""; }

    const h = message.headers;

    let parsed;
    try {
      parsed = parseEmail(raw, MAX_ATTACH_B64);
    } catch (e) {
      parsed = { from: "", fromName: "", subject: "", messageId: "", text: "", html: "", attachments: [] };
    }

    const payload = {
      from: (message.from || h.get("from") || parsed.from || "").toLowerCase().trim(),
      fromName: parsed.fromName || null,
      to: (safeTo(message) || h.get("to") || "").toLowerCase().trim(),
      subject: decodeHeader(h.get("subject") || parsed.subject || "") || "(bez předmětu)",
      text: parsed.text || "",
      html: parsed.html || null,
      messageId: (h.get("message-id") || parsed.messageId || "").trim() || null,
      inReplyTo: (h.get("in-reply-to") || "").trim() || null,
      references: (h.get("references") || "").trim() || null,
      date: h.get("date") || null,
      spam: false,
      attachments: parsed.attachments || [],
    };

    try {
      await fetch(INGEST_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Posta-Token": INGEST_TOKEN },
        body: JSON.stringify(payload),
      });
    } catch (e) { /* aplikace nedostupná – originál stejně přeposíláme */ }

    try { await message.forward(BACKUP_EMAIL); } catch (e) { /* neověřená adresa */ }
  },
};

function safeTo(message) { try { return message.to || ""; } catch (e) { return ""; } }

function parseEmail(raw, maxAttachB64) {
  const out = { from: "", fromName: "", subject: "", messageId: "", text: "", html: "", attachments: [] };
  if (!raw) return out;

  const sep = raw.indexOf("\r\n\r\n") >= 0 ? "\r\n\r\n" : "\n\n";
  const at = raw.indexOf(sep);
  const headBlock = raw.slice(0, at);
  const body = raw.slice(at + sep.length);
  const headers = headBlock.replace(/\r?\n[ \t]+/g, " ");

  const fromRaw = getHeader(headers, "from") || "";
  const fm = fromRaw.match(/^\s*"?([^"<]*)"?\s*<([^>]+)>/);
  if (fm) { out.fromName = decodeHeader(fm[1].trim()); out.from = fm[2].trim().toLowerCase(); }
  else { out.from = fromRaw.trim().toLowerCase(); }
  out.subject = decodeHeader(getHeader(headers, "subject") || "");
  out.messageId = (getHeader(headers, "message-id") || "").trim();

  let attachBytes = 0;
  walkPart(headers, body);

  if (!out.text && out.html) out.text = stripHtml(out.html);
  return out;

  function walkPart(pHead, pBody) {
    const ct = (getHeader(pHead, "content-type") || "").toLowerCase();
    const cte = (getHeader(pHead, "content-transfer-encoding") || "").toLowerCase();
    const cd = getHeader(pHead, "content-disposition") || "";

    if (ct.startsWith("multipart/")) {
      const bm = ct.match(/boundary="?([^"\s;]+)"?/i);
      if (!bm) return;
      for (let part of pBody.split("--" + bm[1])) {
        part = part.replace(/^\r?\n/, "");
        if (part === "" || part.startsWith("--")) continue;
        const psep = part.indexOf("\r\n\r\n") >= 0 ? "\r\n\r\n" : "\n\n";
        const pa = part.indexOf(psep);
        if (pa < 0) continue;
        const subHead = part.slice(0, pa).replace(/\r?\n[ \t]+/g, " ");
        const subBody = part.slice(pa + psep.length).replace(/\r?\n$/, "");
        walkPart(subHead, subBody);
      }
      return;
    }

    const filename = getFilename(pHead);
    const isAttachment = /attachment/i.test(cd) || (filename && !ct.startsWith("text/"));

    if (isAttachment) {
      if (attachBytes >= maxAttachB64) return;
      let b64;
      if (cte === "base64") {
        b64 = pBody.replace(/\s+/g, "");
      } else {
        try { b64 = btoa(unescape(encodeURIComponent(pBody))); } catch (e) { return; }
      }
      if (!b64) return;
      attachBytes += b64.length;
      if (attachBytes > maxAttachB64) return;
      out.attachments.push({
        filename: filename || "priloha",
        mimeType: (ct.split(";")[0] || "application/octet-stream").trim() || "application/octet-stream",
        content: b64,
      });
      return;
    }

    if (ct.startsWith("text/html")) {
      if (!out.html) out.html = decodeBody(pBody, cte).trim();
    } else if (ct.startsWith("text/plain") || ct === "") {
      if (!out.text) out.text = decodeBody(pBody, cte).trim();
    }
  }
}

function getFilename(head) {
  let m = head.match(/filename\*=([^']*)'[^']*'([^;\r\n]+)/i);
  if (m) { try { return decodeURIComponent(m[2].trim().replace(/^"|"$/g, "")); } catch (e) { /* */ } }
  m = head.match(/filename="?([^";\r\n]+)"?/i);
  if (m) return decodeHeader(m[1].trim());
  m = head.match(/\bname="?([^";\r\n]+)"?/i);
  if (m) return decodeHeader(m[1].trim());
  return "";
}

function getHeader(headers, name) {
  const m = headers.match(new RegExp("^" + name + ":\\s*(.*)$", "im"));
  return m ? m[1].trim() : "";
}

function decodeBody(s, cte) {
  try {
    if (cte === "base64") return decodeURIComponent(escape(atob(s.replace(/\s+/g, ""))));
    if (cte === "quoted-printable")
      return decodeURIComponent(escape(s.replace(/=\r?\n/g, "").replace(/=([A-Fa-f0-9]{2})/g, (_, x) => String.fromCharCode(parseInt(x, 16)))));
  } catch (e) { /* fallthrough */ }
  return s;
}

function decodeHeader(s) {
  if (!s) return "";
  return s.replace(/=\?([^?]+)\?([BbQq])\?([^?]*)\?=/g, (_, cs, enc, data) => {
    try {
      let bytes = enc.toUpperCase() === "B"
        ? atob(data)
        : data.replace(/_/g, " ").replace(/=([A-Fa-f0-9]{2})/g, (_, hh) => String.fromCharCode(parseInt(hh, 16)));
      return decodeURIComponent(escape(bytes));
    } catch (e) { return data; }
  }).trim();
}

function stripHtml(html) {
  return html.replace(/<style[\s\S]*?<\/style>/gi, "").replace(/<script[\s\S]*?<\/script>/gi, "")
    .replace(/<br\s*\/?>/gi, "\n").replace(/<\/p>/gi, "\n\n").replace(/<[^>]+>/g, "")
    .replace(/&nbsp;/g, " ").replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
    .replace(/\n{3,}/g, "\n\n").trim();
}
