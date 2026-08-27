/**
 * Parse RFC 4180-style CSV text, including quoted commas, escaped quotes,
 * embedded newlines, CRLF line endings, and an optional UTF-8 BOM.
 */
export function parseCsvRows(input) {
    const text = String(input ?? '').replace(/^\uFEFF/, '');
    const rows = [];
    let row = [];
    let field = '';
    let quoted = false;

    for (let index = 0; index < text.length; index += 1) {
        const character = text[index];

        if (quoted) {
            if (character === '"') {
                if (text[index + 1] === '"') {
                    field += '"';
                    index += 1;
                } else {
                    quoted = false;
                }
            } else {
                field += character;
            }
            continue;
        }

        if (character === '"' && field.length === 0) {
            quoted = true;
        } else if (character === ',') {
            row.push(field.trim());
            field = '';
        } else if (character === '\n' || character === '\r') {
            if (character === '\r' && text[index + 1] === '\n') index += 1;
            row.push(field.trim());
            field = '';
            if (row.some((value) => value !== '')) rows.push(row);
            row = [];
        } else {
            field += character;
        }
    }

    row.push(field.trim());
    if (row.some((value) => value !== '')) rows.push(row);

    return rows;
}
