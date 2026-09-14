# OCR result mapping

Google Cloud Vision TEXT_DETECTION remains the OCR provider. NhlResultParser maps
individual word bounding boxes into visual rows and recognizes German/English
statistic labels. Values to the left belong to away and values to the right to
home, matching the existing NHL result screen convention.

Missing/invalid values remain unset. A missing shot count cannot shift hits,
times or later statistics. Duplicate candidates are omitted. Times require valid
seconds, percentages must be 0–100, and successful power plays cannot exceed
opportunities. Split words/punctuation and decimal commas are accepted.

Teams and explicit scores (e.g. 3 - 2) are read only above the statistics.
Unknown labels, wrapped labels across multiple visual lines, or scores without
a separator require manual entry. The percentage measures completion of the
24 supported fields, not OCR confidence or guaranteed accuracy.

The Orchid listener invokes OCR and replaces the previous image's values.
Tournament context is retained in encrypted screen state; assigned teams stay
locked. The raw recognized text remains visible for review. No extra Google
calls or OCR services are introduced.

Validation uses synthetic bounding-box fixtures plus upload listener tests.
No representative real screenshots were available for an accuracy benchmark.
