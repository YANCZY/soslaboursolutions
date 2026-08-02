export type PdfColumn<T> = {
    header: string;
    value: (record: T) => string | number | null | undefined;
};

type ExportBrandedPdfOptions<T> = {
    title: string;
    records: T[];
    columns: PdfColumn<T>[];
};

export type ExportPdfResult = 'printed' | 'empty' | 'blocked';

const escapePdfValue = (value: string) => {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};

export const exportBrandedPdf = <T>({
    title,
    records,
    columns,
}: ExportBrandedPdfOptions<T>): ExportPdfResult => {
    if (records.length === 0) {
        return 'empty';
    }

    const printWindow = window.open('', '_blank', 'width=1024,height=768');

    if (!printWindow) {
        return 'blocked';
    }

    const generatedAt = new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date());

    const headers = columns
        .map((column) => `<th>${escapePdfValue(column.header)}</th>`)
        .join('');

    const rows = records
        .map((record) => `
            <tr>
                ${columns
                    .map((column) => `<td>${escapePdfValue(String(column.value(record) ?? '-'))}</td>`)
                    .join('')}
            </tr>
        `)
        .join('');

    printWindow.document.write(`
        <!doctype html>
        <html>
            <head>
                <title>${escapePdfValue(title)}</title>
                <style>
                    * {
                        box-sizing: border-box;
                    }

                    body {
                        margin: 0;
                        font-family: Arial, sans-serif;
                        background: #f4f7f8;
                        color: #111827;
                    }

                    .report {
                        padding: 24px;
                    }

                    .hero {
                        background: linear-gradient(135deg, #0b2524 0%, #111827 58%, #061312 100%);
                        color: #ffffff;
                        padding: 28px;
                        border-bottom: 6px solid #0098c7;
                        position: relative;
                    }

                    .hero::after {
                        content: '';
                        position: absolute;
                        right: 28px;
                        bottom: -6px;
                        width: 160px;
                        height: 6px;
                        background: #ed1c24;
                    }

                    .brand {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        margin-bottom: 28px;
                    }

                    .brand-mark {
                        display: grid;
                        gap: 4px;
                        width: 38px;
                    }

                    .brand-mark span {
                        display: block;
                        height: 8px;
                        background: #0098c7;
                    }

                    .brand-mark span:nth-child(1) {
                        width: 38px;
                    }

                    .brand-mark span:nth-child(2) {
                        width: 30px;
                    }

                    .brand-mark span:nth-child(3) {
                        width: 22px;
                    }

                    .brand-name {
                        font-size: 30px;
                        font-weight: 800;
                        letter-spacing: 0.14em;
                    }

                    .brand-name .sos {
                        color: #ed1c24;
                    }

                    .brand-name .labour {
                        color: #ffffff;
                        font-size: 12px;
                        font-weight: 700;
                        letter-spacing: 0.42em;
                        margin-left: 8px;
                        text-transform: uppercase;
                    }

                    .eyebrow {
                        margin: 0 0 8px;
                        font-size: 11px;
                        font-weight: 700;
                        letter-spacing: 0.28em;
                        text-transform: uppercase;
                        color: #d7f3fb;
                    }

                    h1 {
                        margin: 0;
                        font-size: 34px;
                        line-height: 1.1;
                    }

                    .subtitle {
                        margin: 10px 0 0;
                        max-width: 560px;
                        font-size: 13px;
                        line-height: 1.6;
                        color: #d1d5db;
                    }

                    .meta {
                        display: flex;
                        justify-content: space-between;
                        gap: 16px;
                        margin: 18px 0;
                        padding: 12px 16px;
                        border-left: 4px solid #ed1c24;
                        background: #ffffff;
                        font-size: 12px;
                        color: #374151;
                    }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                        overflow: hidden;
                        background: #ffffff;
                        font-size: 10.5px;
                    }

                    th {
                        background: #0b2524;
                        color: #ffffff;
                        border: 1px solid #173f3d;
                        padding: 9px 8px;
                        text-align: left;
                        font-size: 10px;
                        text-transform: uppercase;
                    }

                    td {
                        border: 1px solid #d1d5db;
                        padding: 8px;
                        text-align: left;
                    }

                    tbody tr:nth-child(even) {
                        background: #f8fafc;
                    }

                    @media print {
                        body {
                            background: #ffffff;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }

                        .report {
                            padding: 0;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="hero">
                    <div class="brand">
                        <div class="brand-mark">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>

                        <div class="brand-name">
                            <span class="sos">SOS</span>
                            <span class="labour">Labour Solutions</span>
                        </div>
                    </div>

                    <p class="eyebrow">Personalised Industry Services</p>
                    <h1>${escapePdfValue(title)}</h1>
                    <p class="subtitle">
                        Bringing quality trades people and business together through best-spoke labour hire solutions.
                    </p>
                </div>

                <div class="report">
                    <div class="meta">
                        <div><strong>Generated:</strong> ${escapePdfValue(generatedAt)}</div>
                        <div><strong>Total Records:</strong> ${records.length}</div>
                    </div>

                    <table>
                        <thead>
                            <tr>${headers}</tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();

    return 'printed';
};
