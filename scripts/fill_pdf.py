#!/usr/bin/env python3
"""Befüllt PDF-Formularfelder aus einer JSON-Datei."""
import sys
import json
from pypdf import PdfReader, PdfWriter

def fill_pdf(input_pdf, field_values_json, output_pdf):
    with open(field_values_json, 'r', encoding='utf-8') as f:
        fields = json.load(f)
    
    reader = PdfReader(input_pdf)
    writer = PdfWriter()
    
    # Alle Seiten kopieren
    for page in reader.pages:
        writer.add_page(page)
    
    # Felder nach Seite gruppieren
    fields_by_page = {}
    for field in fields:
        page = field.get('page', 1) - 1  # 0-basiert
        if page not in fields_by_page:
            fields_by_page[page] = {}
        fields_by_page[page][field['field_id']] = field['value']
    
    # Formularfelder befüllen
    for page_num, page_fields in fields_by_page.items():
        writer.update_page_form_field_values(
            writer.pages[page_num],
            page_fields
        )
    
    with open(output_pdf, 'wb') as f:
        writer.write(f)

if __name__ == '__main__':
    if len(sys.argv) != 4:
        print(f"Usage: {sys.argv[0]} <input.pdf> <fields.json> <output.pdf>")
        sys.exit(1)
    fill_pdf(sys.argv[1], sys.argv[2], sys.argv[3])