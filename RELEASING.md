# Releasing

Bump the version in `src/Client.php` (`Client::VERSION`), following [semantic versioning](https://semver.org/), and merge it to `main`. When the new version, such as `0.2.0`, has no tag yet, the release workflow creates the GitHub release and tag `v0.2.0`, and Packagist publishes the tag on its own. Merges that don't change the version release nothing.

If the release workflow fails, fix the cause and re-run it from the Actions tab.

## Keeping up with the API

CI runs every Monday as well as on each push. Its `endpoints` job fails when [the API's OpenAPI description](https://api.mapricot.com/openapi.json) lists an endpoint this client has no method for, and its tests fail when a live response changes. Add or fix the method and its test, and release a new minor version. A new endpoint is added to every Mapricot SDK at the same time.

## One-time setup

- A repository secret or organization secret `MAPRICOT_KEY`: a server key with the `geocoding`, `places`, `routing`, and `ip-geolocation` scopes and a monthly cap, used by the live tests.
- On packagist.org, the `mapricot/mapricot` package submitted from this repository's URL, with the Packagist GitHub app or webhook installed so it updates on each tag.
