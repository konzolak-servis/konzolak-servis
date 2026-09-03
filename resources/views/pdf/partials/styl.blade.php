<style>
    * { font-family: dejavusans, sans-serif; }
    @page { margin: 0 0 44mm 0; }
    body { color: #1f2937; font-size: 9.5pt; line-height: 1.38; }

    /* ---- Hlavičkový pruh ---- */
    .band { background-color: #0F2038; }
    .band td { padding: 5mm 14mm; }
    .band tr:first-child td { padding-bottom: 2.5mm; }
    .band-logo { width: 40%; vertical-align: middle; }
    .band-doc { vertical-align: middle; text-align: right; color: #fff; white-space: nowrap; }
    .band-doc .typ { font-size: 15pt; font-weight: bold; letter-spacing: .01em; }
    .band-doc .num { font-size: 10.5pt; color: #E8C77C; }
    .band .band-firma { padding: 0 14mm 4.5mm; color: #c7cfda; font-size: 6.6pt;
        line-height: 1.3; letter-spacing: -.1px; white-space: nowrap; }
    .band .band-firma strong { color: #E8C77C; }
    .goldrule { height: 0.8mm; background-color: #C8992E; font-size: 0; line-height: 0; }

    .wrap { padding: 5mm 14mm 0; }
    .firma-line strong { color: #374151; }

    /* ---- Bloky ---- */
    .cols { width: 100%; border-collapse: collapse; }
    .cols > td { vertical-align: top; padding-right: 6mm; }
    .label { color: #9ca3af; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .06em; }
    .val { font-size: 10pt; }
    .val strong { font-size: 11pt; color: #0F2038; }

    .sekce { font-size: 8pt; text-transform: uppercase; letter-spacing: .07em;
        color: #0F2038; font-weight: bold; margin: 5mm 0 1.5mm;
        border-bottom: .5px solid #d9dee5; padding-bottom: 1mm; }
    .box { border: .5px solid #d9dee5; border-radius: 1.5mm; padding: 2.5mm 3mm; background: #fbfbfc; }

    /* ---- Tabulka položek ---- */
    table.items { width: 100%; border-collapse: collapse; margin: 2mm 0 3mm; }
    table.items th { background-color: #fff; color: #0F2038; font-size: 7.5pt;
        text-transform: uppercase; letter-spacing: .04em; padding: 2mm 2.5mm; text-align: left;
        border-bottom: 1px solid #0F2038; }
    table.items td { padding: 2mm 2.5mm; border-bottom: .5px solid #e5e7eb; }
    table.items tr:nth-child(even) td { background: #f7f8fa; }
    table.items .num { text-align: right; white-space: nowrap; }

    .totalbar { width: 100%; border-collapse: collapse; margin-top: 1mm; }
    .totalbar td { padding: 2.5mm 3mm; }
    .totalbar .tlabel { text-align: right; color: #6b7280; }
    .totalbar .tsum { text-align: right; background-color: #fff; color: #0F2038;
        border: .8px solid #d9dee5; border-top: 1.4px solid #0F2038;
        font-size: 12pt; font-weight: bold; width: 45mm; }

    .muted { color: #6b7280; }
    .pravni { font-size: 7pt; color: #6b7280; margin-top: 5mm; text-align: justify; line-height: 1.35; }

    .upozorneni { border: .5px solid #d9dee5; border-left: 2px solid #C8992E; background: #fbfbfc;
        padding: 3mm 3.5mm; margin-top: 5mm; font-size: 8.5pt; }
    .upozorneni b { color: #0F2038; }

    /* ---- Patička s podpisy – vždy u spodního okraje stránky (htmlpagefooter) ---- */
    .paticka { padding: 0 14mm; }
    .podpisy { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
    .podpisy td { width: 50%; padding: 8mm 6mm 0; }
    .podpisy .cara { border-top: .5px solid #9ca3af; padding-top: 1.5mm; font-size: 7.5pt; color: #6b7280; }
    .paticka .meta { border-top: .5px solid #e5e7eb; padding-top: 1.5mm; font-size: 6.5pt; color: #9ca3af; text-align: center; }
</style>
