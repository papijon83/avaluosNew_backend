#!/usr/bin/python3

import sys
from pdf2docx import parse

pdf_file = sys.argv[1]
docx_file = sys.argv[2]

# convert pdf to docx
parse(pdf_file, docx_file)