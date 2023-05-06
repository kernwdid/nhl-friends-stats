# OCR

Best results until now with:

```bash
convert test_file.jpeg -colorspace gray -contrast-stretch 0x50% -despeckle -deskew 40% -unsharp 0x1 -median 3 output.png
tesseract -l eng output.png - nobatch digits
```
