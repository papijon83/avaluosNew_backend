#!/usr/bin/python

import sys
from pdf2docx import Converter

pdf_file = sys.argv[1]
docx_file = sys.argv[2]

# convert pdf to docx
cv = Converter(pdf_file)
cv.convert(docx_file, start=1, end=2)      # all pages by default
cv.close()