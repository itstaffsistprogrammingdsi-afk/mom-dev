import sys

import fitz

if len(sys.argv) < 2:
    raise SystemExit("Usage: python stamp-blueprint-pdf.py <file.pdf>")

path = sys.argv[1]
doc = fitz.open(path)
total = doc.page_count

for index, page in enumerate(doc):
    if index == 0:
        continue

    width = page.rect.width
    height = page.rect.height
    left = 40
    right = width - 40
    baseline = height - 24

    page.draw_line(
        fitz.Point(left, height - 32),
        fitz.Point(right, height - 32),
        color=(0.86, 0.89, 0.93),
        width=0.5,
    )

    label = "Blueprint Sistem Notulensi (MoM) - v1.0"
    page.insert_text(
        fitz.Point(left, baseline),
        label,
        fontsize=7.5,
        fontname="helv",
        color=(0.55, 0.58, 0.62),
    )

    page_label = f"Hal. {index + 1} dari {total}"
    label_width = fitz.get_text_length(page_label, fontname="helv", fontsize=7.5)
    page.insert_text(
        fitz.Point(right - label_width, baseline),
        page_label,
        fontsize=7.5,
        fontname="helv",
        color=(0.55, 0.58, 0.62),
    )

doc.save(path, incremental=True, encryption=fitz.PDF_ENCRYPT_KEEP)
doc.close()

print(f"Stamped {total} pages: {path}")
