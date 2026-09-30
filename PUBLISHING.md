# Publishing to Composer / Packagist

1. Push this SDK folder to a public Git repository (GitHub/GitLab).
2. Create an account at https://packagist.org and click **Submit** with your repo URL.
3. Packagist reads `composer.json` (`pokerprovide/sdk`) and lists your package.
4. Tag releases with SemVer git tags (e.g. `git tag 1.0.0 && git push --tags`) — Packagist auto-detects them via the GitHub webhook.
5. Consumers then install with `composer require pokerprovide/sdk`.
