
# NHL Friends Stats

Since it is not possible to track NHL Online Versus Games (unranked) against friends,
I created a web application to track all the scores and show some statistics for you and your friends.

Feel free to use it and if you want to collaborate or have any questions, contact me at any time:
didiwein[at]hotmail.com

<a href="https://www.buymeacoffee.com/didiweinh" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/default-orange.png" alt="Buy Me A Coffee" height="41" width="174"></a>

## OCR

Instead of typing in all the stats manually, In the future I plan to automatically detect stats by taking a picture of the result screen like this:

![Example Picture](/example.jpeg)

### First tests

Best results until now with:

```bash
convert test_file.jpeg -colorspace gray -contrast-stretch 0x50% -despeckle -deskew 40% -unsharp 0x1 -median 3 output.png
tesseract -l eng output.png - nobatch digits
```
