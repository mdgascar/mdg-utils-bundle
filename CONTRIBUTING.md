# Contributing to mdg-utils-bundle

The [organisation guide](https://github.com/mdgascar/.github/blob/main/CONTRIBUTING.md)
and [Code of Conduct](https://github.com/mdgascar/.github/blob/main/CODE_OF_CONDUCT.md)
apply too.

## Where a change belongs

This package only connects [mdgascar/mdg-utils](https://github.com/mdgascar/mdg-utils-php)
to Symfony. A wrong word, phone prefix, amount or date is a core issue: report it in
[mdg-utils-spec](https://github.com/mdgascar/mdg-utils-spec) first. Changes here are about
the Symfony side: constraints, Twig filters, Doctrine types, config and translations.

## Set up

```sh
git clone https://github.com/mdgascar/mdg-utils-bundle.git
cd mdg-utils-bundle
just setup
```

You need PHP 8.3+, Composer and [just](https://github.com/casey/just).

## Before you open a pull request

```sh
just ci   # lint (coding standard, PHPStan max, magic numbers) and tests with 100% coverage
```

- Branch from `dev` as `<type>/<short-name>` (`feat/`, `fix/`, `docs/`, ...) and open the
  PR against `dev`. `main` only takes merges from `dev`.
- Use Conventional Commits for the PR title. Commits must be signed.
- Add a line to `CHANGELOG.md` under a new version heading when users are affected.
