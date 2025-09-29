#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys
import os
import PyPDF2

def extract_text_from_pdf(pdf_path):
    try:
        # فتح ملف PDF في وضع القراءة الثنائية
        with open(pdf_path, 'rb') as file:
            # إنشاء قارئ PDF
            pdf_reader = PyPDF2.PdfReader(file)

            # استخراج النص من كل صفحة
            text = ""
            for page_num in range(len(pdf_reader.pages)):
                page = pdf_reader.pages[page_num]
                text += page.extract_text() + "\n"

            return text
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python pdf_extractor.py <pdf_path>", file=sys.stderr)
        sys.exit(1)

    pdf_path = sys.argv[1]

    if not os.path.exists(pdf_path):
        print(f"Error: File not found: {pdf_path}", file=sys.stderr)
        sys.exit(1)

    text = extract_text_from_pdf(pdf_path)

    if text:
        print(text)
    else:
        print("Error: Failed to extract text from PDF", file=sys.stderr)
        sys.exit(1)
