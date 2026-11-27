# Changelog

`mdgascar/mdg-utils-bundle` follows Semantic Versioning with its own version number.
Merging `dev` into `main` tags the version in the first heading below and creates the
GitHub release; Packagist picks up the tag.

## 0.1.0

First release. Symfony 7.4 LTS and 8, PHP 8.3+, `mdgascar/mdg-utils` ^0.8.

- Validation constraints `MalagasyPhone` and `AriaryAmount`, with messages in English and
  French.
- Doctrine types `mg_phone` (stored as E.164) and `mg_ariary` (stored as DECIMAL), for DBAL 4.
- Twig filters `ariary`, `mg_phone`, `mg_words` and `mg_date`. `mg_words('traditional')` uses one
  name per power of ten (`telo safatsiroa` for 30 000 000).
- Config `mdg_utils.ariary`: grouping, symbol position and decimal separator.
