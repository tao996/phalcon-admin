# Changelog

All notable changes are documented here. The format is based on [Keep a Changelog][keep_a_changelog] and this project adheres to [Semantic Versioning][semantic_versioning].

## [5.23.0](https://github.com/phalcon/cphalcon/releases/tag/v5.23.0) (2026-10-15)

### Tools

- Zephir 1.6.1

### Changed

- Faster SQL generation in `Phalcon\Db\Dialect` and select execution in `Phalcon\Mvc\Model\Query`. [#17660](https://github.com/phalcon/cphalcon/issues/17660) [[doc]](https://docs.phalcon.io/5.23/db-layer/) [[doc]](https://docs.phalcon.io/5.23/db-phql/)
- Faster event dispatch in `Phalcon\Events\Manager`, method detection in `Phalcon\Http\Request` and header sending in `Phalcon\Http\Response`. [#17633](https://github.com/phalcon/cphalcon/issues/17633) [[doc]](https://docs.phalcon.io/5.23/events/) [[doc]](https://docs.phalcon.io/5.23/request/) [[doc]](https://docs.phalcon.io/5.23/response/)
- Faster model hydration in `Phalcon\Mvc\Model` and `Phalcon\Mvc\Model\Resultset\Complex`. [#17632](https://github.com/phalcon/cphalcon/issues/17632) [[doc]](https://docs.phalcon.io/5.23/db-models/)
- Faster rendering and template compilation in `Phalcon\Mvc\View` and `Phalcon\Mvc\View\Engine\Volt\Compiler`. [#17668](https://github.com/phalcon/cphalcon/issues/17668) [[doc]](https://docs.phalcon.io/5.23/views/) [[doc]](https://docs.phalcon.io/5.23/volt/)
- Faster route matching and route index build in `Phalcon\Mvc\Router`. [#17628](https://github.com/phalcon/cphalcon/issues/17628) [[doc]](https://docs.phalcon.io/5.23/routing/)
- Faster router setup in `Phalcon\Mvc\Micro`: the default router is created without its default routes. [#17630](https://github.com/phalcon/cphalcon/issues/17630) [[doc]](https://docs.phalcon.io/5.23/application-micro/)
- Faster service registration and resolution in `Phalcon\Container\Container`. [#17631](https://github.com/phalcon/cphalcon/issues/17631) [[doc]](https://docs.phalcon.io/5.23/container/)
- Faster service resolution in `Phalcon\Di\Di` for service names with no alias. [#17629](https://github.com/phalcon/cphalcon/issues/17629) [[doc]](https://docs.phalcon.io/5.23/di/)

### Added


### Fixed

- `Phalcon\Mvc\Model::__set()` skipping the user-defined setter when `null` is assigned to a relationship alias. [#17662](https://github.com/phalcon/cphalcon/issues/17662) [[doc]](https://docs.phalcon.io/5.23/db-models-relationships/)
- `Phalcon\Mvc\Router::getMatches()` and `Phalcon\Cli\Router::getMatches()` returning the matches of an earlier request after a static-route match or no match. [#17646](https://github.com/phalcon/cphalcon/issues/17646) [[doc]](https://docs.phalcon.io/5.23/routing/) [[doc]](https://docs.phalcon.io/5.23/application-cli/)
- `Phalcon\Mvc\Router::getMatches()` returning the internal `MARK` key after a combined-regex match. [#17634](https://github.com/phalcon/cphalcon/issues/17634) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\Router` combined-regex matching changing the meaning of a route pattern with `|` outside a group. [#17645](https://github.com/phalcon/cphalcon/issues/17645) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\Router` combined-regex matching failing to compile or mixing matches for routes with named groups. [#17636](https://github.com/phalcon/cphalcon/issues/17636) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\Router` combined-regex matching returning a wrong route when a route pattern in the same chunk does not compile. [#17644](https://github.com/phalcon/cphalcon/issues/17644) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\Router` combined-regex matching skipping a route after a `beforeMatch` veto and calling the callback two times. [#17635](https://github.com/phalcon/cphalcon/issues/17635) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\Router` static-route matching calling a `beforeMatch` callback two times after a veto. [#17638](https://github.com/phalcon/cphalcon/issues/17638) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\Router` static-route matching ignoring a later-attached regex route of the request method. [#17642](https://github.com/phalcon/cphalcon/issues/17642) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\Router` static-route matching passing over a regex route when a later static route is skipped by its host name or `beforeMatch`. [#17643](https://github.com/phalcon/cphalcon/issues/17643) [[doc]](https://docs.phalcon.io/5.23/routing/)
- `Phalcon\Mvc\View\Engine\Volt\Compiler` using the blocks and the `{% extends %}` state of an earlier template when one compiler compiles more than one template. [#17668](https://github.com/phalcon/cphalcon/issues/17668) [[doc]](https://docs.phalcon.io/5.23/volt/)

### Removed


## [5.22.1](https://github.com/phalcon/cphalcon/releases/tag/v5.22.1) (2026-10-01)

### Tools

- Zephir 1.5.0

### Changed

- Faster `Phalcon\Support\Settings::get()`. [#17620](https://github.com/phalcon/cphalcon/issues/17620)
- Faster creation of `Phalcon\Di\FactoryDefault` and faster shared service lookups in `Phalcon\Di\Di`. [#17617](https://github.com/phalcon/cphalcon/issues/17617)
- Faster creation of `Phalcon\Filter\Filter`. [#17617](https://github.com/phalcon/cphalcon/issues/17617)
- Faster route definition and route index build in `Phalcon\Mvc\Router`. [#17618](https://github.com/phalcon/cphalcon/issues/17618)
- Faster service definition and alias lookups in `Phalcon\Container\Container`. [#17619](https://github.com/phalcon/cphalcon/issues/17619)

### Added

- Benchmark suite in `tests/benchmarks` (PHPBench, instruction counts, memory, reference apps) and a `bench` Docker service to compare builds of the extension. [#17613](https://github.com/phalcon/cphalcon/issues/17613)
- Own-cost attribution and static scan tools for the benchmark suite (`tests/benchmarks/bin`). [#17615](https://github.com/phalcon/cphalcon/issues/17615)

### Fixed

- `Phalcon\Filter\Filter::sanitize()` not calling array callables registered with `set()`. [#17610](https://github.com/phalcon/cphalcon/issues/17610) [[doc]](https://docs.phalcon.io/5.22/filter-filter/)
- `Phalcon\Mvc\Model\MetaData\Stream` letting other processes read a partially written cache file. [#17608](https://github.com/phalcon/cphalcon/issues/17608) [[doc]](https://docs.phalcon.io/5.22/db-models-metadata/)
- `Phalcon\Mvc\Model\MetaData` storing a cached value that is not an array as the model meta-data. [#17608](https://github.com/phalcon/cphalcon/issues/17608) [[doc]](https://docs.phalcon.io/5.22/db-models-metadata/)
- `Phalcon\Mvc\View::partial()` and `Phalcon\Mvc\View\Simple::render()` dropping a `..` path segment instead of resolving it. [#17606](https://github.com/phalcon/cphalcon/issues/17606) [[doc]](https://docs.phalcon.io/5.22/views/)
- `Phalcon\Mvc\View::partial()` not detecting a Windows absolute path (`C:\...`) as absolute. [#17606](https://github.com/phalcon/cphalcon/issues/17606) [[doc]](https://docs.phalcon.io/5.22/views/)

### Removed


## [5.22.0](https://github.com/phalcon/cphalcon/releases/tag/v5.22.0) (2026-09-22)

### Tools

- Zephir 1.5.0

### Changed

- `Phalcon\Annotations\ReaderInterface` no longer declares the static `parseDocBlock()`; it stays a public static method of `Phalcon\Annotations\Reader`. [#17601](https://github.com/phalcon/cphalcon/issues/17601) [[doc]](https://docs.phalcon.io/5.22/annotations/)
- `Phalcon\Mvc\Model\MetaData\Strategy\Annotations` reads `skipOnInsert`, `skipOnUpdate`, `allowEmptyString` and `defaultValue` next to the `skip_on_insert`, `skip_on_update`, `allow_empty_string` and `default` it already read. [#17601](https://github.com/phalcon/cphalcon/issues/17601) [[doc]](https://docs.phalcon.io/5.22/db-models-metadata/)

### Added

- `Phalcon\Annotations\AttributesReader` reads PHP attributes instead of docblocks; `setReader()` on the annotations adapter selects it and `Phalcon\Annotations\Reader` stays the default. [#17601](https://github.com/phalcon/cphalcon/issues/17601) [[doc]](https://docs.phalcon.io/5.22/annotations/)
- `Phalcon\Annotations\Router\Route`, `Connect`, `Delete`, `Get`, `Head`, `Options`, `Patch`, `Post`, `Purge`, `Put`, `Trace`, `RoutePrefix` and `Phalcon\Annotations\Models\MetaData\Column`, `Identity`, `Primary`, `Source` give the attribute form of the router and model metadata annotations. [#17601](https://github.com/phalcon/cphalcon/issues/17601) [[doc]](https://docs.phalcon.io/5.22/annotations/)

### Fixed


### Removed


## [5.21.0](https://github.com/phalcon/cphalcon/releases/tag/v5.21.0) (2026-09-18)

### Tools

- Zephir 1.5.0

### Changed

- `Phalcon\Db\Adapter\Pdo\AbstractPdo::commit()` and `rollback()` now throw `Phalcon\Db\Exceptions\NoActiveTransaction` when the connection has no active transaction, instead of sending a commit, rollback or savepoint statement to the server. [#17546](https://github.com/phalcon/cphalcon/issues/17546) [[doc]](https://docs.phalcon.io/5.21/db-layer/)

### Added

- `Phalcon\Mvc\Model::findFirst()` now recognizes the `eager` parameter, so relations can be eagerly loaded. [#17534](https://github.com/phalcon/cphalcon/issues/17534) [[doc]](https://docs.phalcon.io/5.21/db-models/)

### Fixed

- PHQL `WITH` naming a model instead of a relation alias always throwing `RelationshipNotFound`; the fallback checked `Phalcon\Mvc\Model\Manager::getRelationsBetween()` for an object, but it returns an array. Ambiguous pairs now throw `AmbiguousJoinRelation`. [#17554](https://github.com/phalcon/cphalcon/issues/17554) [[doc]](https://docs.phalcon.io/5.21/db-models/)
- PHQL string literals not resolving their escape sequences, so `\n` reached the database as a backslash and an `n`. [#17585](https://github.com/phalcon/cphalcon/issues/17585) [[doc]](https://docs.phalcon.io/5.21/db-phql/)
- `Phalcon\Db\Adapter\Pdo\AbstractPdo` leaving the transaction nesting level wrong when `begin()`, `commit()` or `rollback()` fails. [#17546](https://github.com/phalcon/cphalcon/issues/17546) [[doc]](https://docs.phalcon.io/5.21/db-layer/)
- `Phalcon\Filter\Validation\Validator\File\AbstractFile::checkUpload()` reporting success when the field value is not an uploaded file array; a missing file or a plain string now fails validation. [#17541](https://github.com/phalcon/cphalcon/issues/17541) [[doc]](https://docs.phalcon.io/5.21/filter-validation/)
- `Phalcon\Filter\Validation\Validator\File\Resolution\Equal`, `Max`, `Min` and `AspectRatio` not checking the `false` returned by `getimagesize()`; a file that is not a readable image is now rejected. [#17542](https://github.com/phalcon/cphalcon/issues/17542) [[doc]](https://docs.phalcon.io/5.21/filter-validation/)
- `Phalcon\Filter\Validation\Validator\Ip` ignoring per-field `allowPrivate` and `allowReserved` arrays; the option is now resolved for the field before it becomes a filter flag. [#17548](https://github.com/phalcon/cphalcon/issues/17548) [[doc]](https://docs.phalcon.io/5.21/filter-validation/)
- `Phalcon\Forms\Element\CheckGroup` and `RadioGroup` storing their choices in the property `Phalcon\Forms\Element\AbstractElement` uses for user options, so `setUserOption()` added a choice and `setOptions()` removed every user option. [#17536](https://github.com/phalcon/cphalcon/issues/17536) [[doc]](https://docs.phalcon.io/5.21/forms/)
- `Phalcon\Forms\Element\Select::addOption()` writing with an offset into an object or `null` options value; the write now happens only when the options value is an array (`null` becomes an empty array). [#17536](https://github.com/phalcon/cphalcon/issues/17536) [[doc]](https://docs.phalcon.io/5.21/forms/)
- `Phalcon\Mvc\Model\Manager::getRelationRecords()` raising `TypeError` on a compound through-relation marked `reusable`, and reusing one key for every record. The key is now built from every field the relation covers. [#17560](https://github.com/phalcon/cphalcon/issues/17560) [[doc]](https://docs.phalcon.io/5.21/db-models-relationships/)
- `Phalcon\Mvc\Model\Manager` relation guards throwing `TypeError` on a string field list, and the second check in `addHasManyToMany()` / `addHasOneThrough()` comparing the same pair twice. [#17556](https://github.com/phalcon/cphalcon/issues/17556) [[doc]](https://docs.phalcon.io/5.21/db-models/)
- `Phalcon\Mvc\Model\Resultset::valid()` reporting no row for a resultset restored from cache; `Phalcon\Mvc\Model\Resultset\Complex` stores rows that are already hydrated, so they are objects and not arrays. [#17574](https://github.com/phalcon/cphalcon/issues/17574) [[doc]](https://docs.phalcon.io/5.21/db-models/)
- `Phalcon\Mvc\Model\Resultset\Simple::current()` giving back `false` on a second call at a position with no row; the `false` it caches is a sentinel, and `null` is returned as the declared type says. [#17568](https://github.com/phalcon/cphalcon/issues/17568) [[doc]](https://docs.phalcon.io/5.21/db-models/)
- `Phalcon\Mvc\Model` raising `TypeError` on a composite virtual foreign key violation, and when the case-insensitive column-map lookup ran without a column map. [#17558](https://github.com/phalcon/cphalcon/issues/17558) [[doc]](https://docs.phalcon.io/5.21/db-models/)
- `Phalcon\Mvc\View\Engine\Volt::preload()` raising an error when the engine has no container; the href is given back unchanged. `Phalcon\Mvc\View\Engine\Volt\Compiler::compileSource()` returning `null` in extends mode when the template contributes no blocks. [#17565](https://github.com/phalcon/cphalcon/issues/17565) [[doc]](https://docs.phalcon.io/5.21/volt/)
- `Phalcon\Storage\Adapter\Stream` and `Phalcon\Queue\Adapter\Stream\StreamContext` reporting a `mkdir(): File exists` warning when a different process makes the directory first. [#17561](https://github.com/phalcon/cphalcon/issues/17561) [[doc]](https://docs.phalcon.io/5.21/storage/)

### Removed


## [5.20.3](https://github.com/phalcon/cphalcon/releases/tag/v5.20.3) (2026-08-26)

### Tools

- Zephir 1.3.0

### Changed

- ACL role, component and access names can no longer contain `!`, the internal key delimiter; `Phalcon\Acl\Exceptions\ForbiddenDelimiter` is thrown instead.
- Cache file names of `Phalcon\Annotations\Adapter\Stream`, `Phalcon\Mvc\Model\MetaData\Stream` and `Phalcon\Storage\Adapter\Stream` get a hash suffix when the key contains the character that the separator replacement produces (`_` for class names; `/`, `\`, `:` for storage keys), so two different keys can no longer share one file. Names of all other keys are unchanged.
- `Phalcon\Auth\Guard\Session` sets the `Secure` flag of the remember-me cookie from the new `rememberSecure` option (default `true`) instead of the request scheme, so a TLS-terminating proxy that reports plain HTTP to the backend cannot downgrade it.
- `Phalcon\Auth\Guard\Session` validates the "remember me" token against the user agent of the current request instead of the one stored in the cookie; a browser user-agent change now ends a remembered session.
- `Phalcon\Encryption\Security::CRYPT_MD5`, `CRYPT_SHA256` and `CRYPT_SHA512` are documented as weak legacy algorithms to be removed in a future major version; use bcrypt or Argon2 and rehash on login.
- `Phalcon\Storage\Adapter\Stream` creates its shard directories with mode `0755` instead of `0777`. Thanks to [Ilia Alshanetsky](https://ilia.ws)

### Added

- Optional fifth argument `stopOnFalse` on `Phalcon\Events\Manager::fire()` (not on the interface), a per-call override of `setStopOnFalse()`; `EventsAwareTrait::fireManagerEvent()` gained a matching fourth argument.
- `Phalcon\Acl\Exceptions\ForbiddenDelimiter`, thrown when an ACL role, component or access name contains `!`.
- `Phalcon\Auth\Exceptions\InvalidCredentialKey`, thrown when a credential key passed to `Phalcon\Auth\Adapter\Model::retrieveByCredentials()` is not a plain identifier.
- `Phalcon\Http\Request\Bag\AbstractBag::clear()`, removing all elements of a request bag.
- `allowedClasses` option for the Storage adapters (`true`, `false` or a list of class names), forwarded to the new `Phalcon\Storage\Serializer\Php::setAllowedClasses()`: restricts the classes `unserialize()` may instantiate for stored values, including the nested content of the `Stream` adapter. A class outside the list makes the read fail instead of building an object. Thanks to [Ilia Alshanetsky](https://ilia.ws)
- `rememberSecure` option for the session guard (`Phalcon\Auth\Guard\Config\SessionGuardConfig`, `Session::fromOptions()`).

### Fixed

- "Remember me" cookie of another account surviving `Phalcon\Auth\Guard\Session::logout()` when the current user does not implement `AuthRemember`.
- A `false` returned by a listener of `acl:beforeCheckAccess`, `dispatch:beforeDispatch`, `dispatch:beforeExecuteRoute`, `micro:beforeHandleRoute` or `micro:beforeExecuteRoute` being overwritten by a later listener that returned a non-null value; these boundaries now fire with stop-on-false, so a denial is final.
- Asset output following a symbolic link at the target file and writing outside the assets directory.
- Backslash path traversal in `Phalcon\Mvc\View::partial()` and `Phalcon\Mvc\View\Simple::render()` on Windows.
- Cached user surviving `Phalcon\Auth\Guard\Token::setRequest()`, so a replaced request inherited the previous authentication.
- Column comment concatenated unescaped into the `CREATE TABLE` / `ALTER TABLE` DDL of the MySQL dialect; it is now escaped like the DEFAULT clause. Thanks to [Ilia Alshanetsky](https://ilia.ws)
- Credential keys interpolated unvalidated into the PHQL built by `Phalcon\Auth\Adapter\Model::retrieveByCredentials()`.
- Distinct ACL tuples colliding on the same internal key when a role, component or access name contained `!`.
- Fixed shared memory leak of response headers into other callers
- JWT audience validated with a loose comparison, so a numeric or boolean `aud` claim satisfied a string audience.
- Length-dependent HMAC work on the CBC decrypt failure path of `Phalcon\Encryption\Crypt`, which could still tell a padding failure from a MAC mismatch by timing.
- Malformed ACL snapshot loaded by `Phalcon\Acl\Adapter\Storage` raising `TypeError` or leaving the adapter half loaded, and deep or cyclic object graphs recursing without limit; `InvalidSnapshot` is now thrown before any state changes.
- Memory leak in the PHQL parser for every rejected literal when `phalcon.orm.enable_literals` is off.
- Memory leak of the error message on every failed `Phalcon\Mvc\Model\Query\Lang::parsePHQL()` call (syntax and scanner errors); the string was only released at request shutdown.
- Memory leak of the message buffer on every Volt syntax error (`Phalcon\Mvc\View\Engine\Volt\Compiler`); the buffer was only released at request shutdown.
- Namespace middleware bypass in the ADR `Router` through case-variant or separator-injected paths that PHP resolves to the canonical Action class; only the exact declared class name is a match.
- Non-string elements passed to `Phalcon\Acl\Adapter\Memory::addInherit()` raising a warning and a `TypeError` instead of `InvalidRoleType`.
- Out-of-bounds read in the Annotations scanner when a docblock ends outside an annotation (for example `@!`); the scanner now stops at the end of the input and `Reader::parseDocBlock()` returns `false` instead of an unset value for a docblock without annotations.
- Out-of-bounds read in the Annotations, Volt and PHQL scanners when a quoted string ends with a backslash: the escape rule could consume the string terminator.
- Quadratic list building in the Annotations, Volt and PHQL parsers; argument lists, `IN` lists and array literals with tens of thousands of items now parse in linear time.
- Request attributes of the previous route surviving on a reused request in `Phalcon\ADR\Application::handle()`.
- Scheme allow-list bypass in the Filter `url` sanitizer through HTML-entity obfuscated schemes (`java&#115;cript:`) and URLs that `parse_url()` cannot parse; the sanitizer now fails closed. Thanks to [Ilia Alshanetsky](https://ilia.ws)
- Validators `Alpha`, `Alnum`, `Confirmation`, `CreditCard`, `Digit`, `Numericality`, `Regex`, `StringLength\Min` and `StringLength\Max` cast an array value to the constant `"Array"`, so `field[]=x` passed alphabetic, alphanumeric, length and confirmation checks; a value that cannot be a string is now rejected with the validator's message. Thanks to [Ilia Alshanetsky](https://ilia.ws)
- Volt extends-mode cache unserialized without a class restriction.
- `ReflectionException` / `TypeError` from ACL rule callbacks with builtin-typed parameters, array callables or static-method strings.
- `acl:afterCheckAccess` reporting the static rule instead of the final `isAllowed()` decision (rule callback and default action were not applied).
- `only()` / `except()` action filters leaking between `Phalcon\Auth\Manager::access()` activations when the access gate was registered as a shared service in the legacy `Di`.

### Removed


## [5.20.2](https://github.com/phalcon/cphalcon/releases/tag/v5.20.2) (2026-08-25)

### Tools

- Zephir 1.3.0

### Changed

- Added regression tests for the hardening changes released in 5.20.0 and 5.20.1 that had no failing-on-revert coverage.
- The PHPUnit configuration now fails the run on notices, deprecations and PHPUnit deprecations, and prints the details of every triggering test.

### Added

### Fixed

- `Undefined index` notice emitted for every literal route when `Phalcon\Mvc\Router` rebuilds its per-method index. [#17527](https://github.com/phalcon/cphalcon/issues/17527)

### Removed

## [5.20.1](https://github.com/phalcon/cphalcon/releases/tag/v5.20.1) (2026-08-24)

### Tools

- Zephir 1.2.0 (32d04d5)

### Changed

### Added

### Fixed

- Attribute name and empty-option injection in the legacy Tag / Forms Select element.
- Attribute name and tag name injection in the Html Tag helpers.
- Attribute name splitting in the Html Escaper `attributes()` array keys.
- Backslash breakout in the Html Escaper `js()` output.
- Broken string-literal escaping of column defaults and ENUM/SET values in the Db dialects.
- CBC padding-oracle exposure in `Crypt::decrypt()`, where a padding failure was distinguishable from a MAC mismatch.
- Command injection via CR/LF in Beanstalk queue tube names.
- CORS credentials paired with a reflected wildcard origin in the ADR Cors middleware.
- Dangerous URL scheme (`javascript:`, `data:`) passthrough in the Filter `url` sanitizer.
- Format-string denial of service in the Translate indexed-array interpolator.
- Log injection via unescaped control characters in the Logger line formatter.
- Missing Secure and HttpOnly flags on the Auth "remember me" cookie.
- Non-constant-time credential comparison in the Auth memory and stream adapters.
- Non-cryptographic randomness in the ISO-10126 padding scheme.
- Object injection through unrestricted `unserialize()` in the Annotations Stream adapter.
- Object injection through unrestricted `unserialize()` in the Storage Stream payload read.
- Only the first configured trusted proxy checked when resolving the Request client address.
- Open redirect in the ADR redirect responder bypassing the Http Response `redirect()` gate.
- Open redirect via absolute and protocol-relative targets in the Http Response `redirect()`.
- Path traversal in the View `partial()` and Simple `render()` paths.
- Path traversal via the cache key in the Storage Stream adapter.
- Path traversal via the session id in the Session Stream adapter.
- Reflected XSS via unescaped array keys in the Debug dump and exception renderer.
- SQL injection in the Db Dialect schema-introspection methods.
- SQL injection via uncast LIMIT and OFFSET values in the Db Dialect `limit()`.
- Session fixation in the Auth Session guard login.
- Session id not regenerated on the Auth Session guard logout.
- Unbounded image dimensions (decompression bomb / pixel flood) in the GD and Imagick image adapters.
- Unbounded recursion in the WKB geometry parser.
- Unescaped CSS class in Flash messages.
- Unescaped identifier quoting in the DataMapper PDO connection.
- Unescaped label and link in the deprecated Html Breadcrumbs.
- Unescaped label text in the Html checkbox and radio helpers.
- Unescaped link in the Html breadcrumbs helper.
- Wrong route dispatched by the Router fast path when a static route follows a shadowing regex.

### Removed

## [5.20.0](https://github.com/phalcon/cphalcon/releases/tag/v5.20.0) (2026-08-22)

### Tools

- Zephir 1.2.0 (32d04d5)

### Changed

### Added

### Fixed

- Single-quoted string emission in Volt Compiler.
- Quoted parameters in PHQL/Query
- Fixed warnings when compiling from sources when Memcached, Redis etc. are not present [17517](https://github.com/phalcon/cphalcon/issues/17517)

### Removed

## [5.19.0](https://github.com/phalcon/cphalcon/releases/tag/v5.19.0) (2026-08-19)

### Tools

- Zephir 1.2.0 (a09648c)

### Changed

- Changed `Phalcon\Filter\Validation\Validator\Callback` to stop binding the callback closure to the validator; to set the message from inside the callback ([#17255](https://github.com/phalcon/cphalcon/issues/17255)), declare a second parameter and call `$validator->setTemplate()` instead of `$this->setTemplate()` [#17499](https://github.com/phalcon/cphalcon/issues/17499) [[doc]](https://docs.phalcon.io/5.19/filter-validation/)
- Changed `Phalcon\Filter\Validation\Validator\StringLength`, `Phalcon\Filter\Validation\Validator\StringLength\Min` and `Phalcon\Filter\Validation\Validator\StringLength\Max` to treat `min` and `max` as inclusive when the `included` option is not set, matching the class documentation and the behavior before 5.7.0; set `included` to `false` for exclusive boundaries [#17503](https://github.com/phalcon/cphalcon/issues/17503) [[doc]](https://docs.phalcon.io/5.19/filter-validation/)

### Added

- Added `includedMinimum` and `includedMaximum` as aliases of the `included` option in `Phalcon\Filter\Validation\Validator\StringLength\Min` and `Phalcon\Filter\Validation\Validator\StringLength\Max`, so the option names of the `StringLength` container also work on the two validators directly. `included` has precedence if you set both [#17503](https://github.com/phalcon/cphalcon/issues/17503) [[doc]](https://docs.phalcon.io/5.19/filter-validation/)
- Added lifecycle events to the DataMapper connections, fired through `Phalcon\Events\Manager` and named on the new `Phalcon\DataMapper\Pdo\Events` class: `dm:beforeConnect`/`dm:afterConnect`, `dm:beforeDisconnect`/`dm:afterDisconnect`, `dm:beforePerform`/`dm:afterPerform`, `dm:beforeExec`/`dm:afterExec`, `dm:beforeQuery`/`dm:afterQuery`, `dm:beforeBeginTransaction`/`dm:afterBeginTransaction`, `dm:beforeCommit`/`dm:afterCommit`, `dm:beforeRollBack`/`dm:afterRollBack` and `dm:connectionLost`. `prepare()` has no events of its own because `perform()` calls it, which would report one operation twice. The connect, disconnect and connectionLost events report a change of the connection state and fire whichever method causes it, so an automatic reconnect reports the lost connection and the new one. `Phalcon\DataMapper\Pdo\Connection\Decorated` does not fire the connect and disconnect events because it never connects and cannot disconnect. [#17501](https://github.com/phalcon/cphalcon/issues/17501) [[doc]](https://docs.phalcon.io/5.19/datamapper/)
- Added `getEventsManager()` and `setEventsManager()` to `Phalcon\DataMapper\Pdo\Connection\AbstractConnection`, and therefore to `Phalcon\DataMapper\Pdo\Connection` and `Phalcon\DataMapper\Pdo\Connection\Decorated`, and to `Phalcon\DataMapper\Pdo\ConnectionLocator`, which gives its events manager to every connection it returns, including the ones it builds on demand. The two classes now carry the `Phalcon\Contracts\Events\EventsAware` contract. `Phalcon\DataMapper\Pdo\Connection\ConnectionInterface` is unchanged, so classes that implement it directly keep working [#17501](https://github.com/phalcon/cphalcon/issues/17501) [[doc]](https://docs.phalcon.io/5.19/datamapper/)
- Added `Phalcon\DataMapper\Pdo\Exception\OperationCancelled`, thrown when a listener cancels one of the `before*` events. The operation does not run. To cancel, a listener must call `$event->stop()` and also return `false`: `stop()` alone returns the value of the listener, which the connection cannot tell apart from "no listeners", and `false` alone is replaced by any later listener that returns a value while the events manager is not in `stopOnFalse` mode. The `after*` events are not cancellable [#17501](https://github.com/phalcon/cphalcon/issues/17501) [[doc]](https://docs.phalcon.io/5.19/datamapper/)

### Fixed

- Fixed `Phalcon\Filter\Validation\Validator\Callback` rebinding `$this` in callback closures; the validator is now passed as a second argument to closures that declare one [#17499](https://github.com/phalcon/cphalcon/issues/17499) [[doc]](https://docs.phalcon.io/5.19/filter-validation/)
- Fixed `Phalcon\Filter\Validation\Validator\StringLength\Min` and `Phalcon\Filter\Validation\Validator\StringLength\Max` rejecting a string with a length exactly equal to `min` or `max` when the `included` option was not set, a regression introduced in 5.7.0 [#17503](https://github.com/phalcon/cphalcon/issues/17503) [[doc]](https://docs.phalcon.io/5.19/filter-validation/)
- Fixed `Phalcon\Filter\Validation\Validator\StringLength` giving the `includedMinimum`/`includedMaximum` and `messageMinimum`/`messageMaximum` option of one boundary to the validator of the other boundary [#17503](https://github.com/phalcon/cphalcon/issues/17503) [[doc]](https://docs.phalcon.io/5.19/filter-validation/)
- Fixed `Phalcon\Html\Helper\Input\Generic`, `Phalcon\Html\Helper\Input\Checkbox` and `Phalcon\Html\Helper\Input\Radio` throwing an error when you build them directly without a `Phalcon\Html\Helper\Doctype`. [#17507](https://github.com/phalcon/cphalcon/issues/17507) [[doc]](https://docs.phalcon.io/5.19/html-tagfactory/)
- Fixed `Phalcon\Image\Adapter\Imagick::processReflection()` applying the reflection to the first frame only of a multi-frame image, because the first two loops changed the `reflection` clone but moved the frame cursor of `this->image` [#17510](https://github.com/phalcon/cphalcon/issues/17510) [[doc]](https://docs.phalcon.io/5.19/image/)
- Fixed `Phalcon\Image\Adapter\Imagick::background()`, `reflection()`, `text()` and `watermark()` discarding any opacity below 100, and `sharpen()` rounding its amount down, because the division by 100 was stored back in the integer parameter; `watermark()` left the image unchanged for every opacity except 100 [#17510](https://github.com/phalcon/cphalcon/issues/17510) [[doc]](https://docs.phalcon.io/5.19/image/)
- Fixed `Phalcon\Image\Adapter\Imagick` never coalescing the frames of a GIF, because the constructor compared `getImageType()`, an Imagick `IMGTYPE_*` value, against `IMAGETYPE_GIF`; the width and the height of an animated GIF now describe the canvas instead of whichever sub frame the cursor was on [#17510](https://github.com/phalcon/cphalcon/issues/17510) [[doc]](https://docs.phalcon.io/5.19/image/)
- Fixed `Phalcon\Image\Adapter\Imagick::render()` returning the current frame alone for a GIF, and `save()` failing with `no encode delegate for this image format` after an operation that rebuilds the image; both now mark every frame with the format, which `setImageFormat()` applies to the current frame only [#17510](https://github.com/phalcon/cphalcon/issues/17510) [[doc]](https://docs.phalcon.io/5.19/image/)

### Removed

## [5.18.2](https://github.com/phalcon/cphalcon/releases/tag/v5.18.2) (2026-08-03)

### Tools

- Zephir 1.2.0 (83d8f68)

### Changed

- Changed the distribution tarball to ship the generated `ext/` sources again, so downstream packagers can build from them. Only tests, Zephir sources, CI and tooling stay excluded. [#17478](https://github.com/phalcon/cphalcon/issues/17478)

### Added

### Fixed

### Removed

## [5.18.1](https://github.com/phalcon/cphalcon/releases/tag/v5.18.1) (2026-08-02)

### Tools

- Zephir 1.2.0 (83d8f68)

### Changed

### Added

### Fixed

- Fixed `Phalcon\Mvc\View::partial()` and `render()` throwing `ViewNotFound` for absolute paths [#17426](https://github.com/phalcon/cphalcon/issues/17426)
- Removed a stray "!" in `Phalcon\Db\Adapter\Pdo\Mysql::unquoteDefault()` to remove the compilation warning [#17427](https://github.com/phalcon/cphalcon/issues/17427)
17469

### Removed

## [5.18.0](https://github.com/phalcon/cphalcon/releases/tag/v5.18.0) (2026-07-31)

### Tools

- Zephir 1.2.0 (83d8f68)

### Changed

- Changed `Phalcon\ADR\Application` into a self-contained composition root: it owns (or accepts) a `Phalcon\Container\Container` and exposes a small registration surface - `bind()`, `define()`, `factory()`, `set()`, `extend()` and `getContainer()` - plus `setBaseNamespace()` and `secureWith()` for convention-router and namespace-prefix guard configuration. `Phalcon\ADR\Front\AbstractHttpFront` gained a protected `getApplication()` hook returning `Phalcon\Contracts\ADR\Application`, so a front controller can configure the application or wire a different implementation. [#17389](https://github.com/phalcon/cphalcon/issues/17389) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Changed `Phalcon\ADR\Router\Router` from a candidate chain to a namespace descent: a path segment becomes a namespace segment only if the matching directory exists, after which at most two Action classes are probed instead of five. An Action can no longer be silently shadowed by an earlier candidate. Requires `setActionDirectory()`. [#17405](https://github.com/phalcon/cphalcon/issues/17405) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Changed `Phalcon\ADR\Router\Router` to derive exactly one Action class per path: the class name is the verb followed by every static segment concatenated. [#17410](https://github.com/phalcon/cphalcon/issues/17410) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Changed `Phalcon\Events\Manager::getEventTypes()` to `getListenerMap()`, which now returns each event type mapped to its listeners. [#17416](https://github.com/phalcon/cphalcon/issues/17416) [[doc]](https://docs.phalcon.io/5.18/events/)
- Changed `Phalcon\Mvc\Model::getRelated()` and `Phalcon\Mvc\Model::isRelationshipLoaded()` to test the relation cache with `array_key_exists()` instead of `isset()`, so a to-one relation that resolves to no record is no longer re-queried on every access [#17331](https://github.com/phalcon/cphalcon/issues/17331) [[doc]](https://docs.phalcon.io/5.18/db-models-relationships/)
- Changed `Phalcon\Mvc\Model\Manager::mergeFindParameters()` from `final protected` to `final public static` [#17331](https://github.com/phalcon/cphalcon/issues/17331)
- Changed `Phalcon\Mvc\Model\Resultset::refresh()` to reset the cursor - position, current row, buffered rows and active row - after replaying the statement. [#17399](https://github.com/phalcon/cphalcon/issues/17399) [[doc]](https://docs.phalcon.io/5.18/db-models/)
- Changed the distribution tarball that Composer and PIE download to ship only the files needed to build the extension; tests, Zephir sources, tooling and the Docker development setup are now excluded through `.gitattributes`. [#17419](https://github.com/phalcon/cphalcon/issues/17419)
- Replaced fully-qualified class-name strings with `::class` references (and `use` imports) across factories, service providers and DI containers [#17380](https://github.com/phalcon/cphalcon/issues/17380)

### Added

- Added `Phalcon\ADR\Front\AbstractHttpFront::boot()`, which builds the container, loads the environment and registers the providers, then returns the container - for consumers that need it before, or instead of, `run()`. The container is built once and cached, so `boot()` and `run()` share the same instance. A bootstrap file can now be `return (new AppFront(dirname(__DIR__)))->boot();`. [#17413](https://github.com/phalcon/cphalcon/issues/17413) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Added `Phalcon\ADR\Responder\ViewResponder`, which renders a `.phtml` template and returns it as an HTML response. The action picks the template with `withTemplate()`, and the view receives `result`, `messages` and `status`. Any renderer implementing the new `Phalcon\Contracts\View\Renderer` can be used - `Phalcon\Mvc\View\Simple` now does. [#17379](https://github.com/phalcon/cphalcon/issues/17379) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Added `Phalcon\Container\Container::getServiceNames()`, returning the names of every registered service definition. [#17406](https://github.com/phalcon/cphalcon/issues/17406) [[doc]](https://docs.phalcon.io/5.18/container/)
- Added `Phalcon\Contracts\Container\Service\Enumerable` implemented in `Phalcon\Container\Container`. [#17416](https://github.com/phalcon/cphalcon/issues/17416) [[doc]](https://docs.phalcon.io/5.18/container/)
- Added `Phalcon\Contracts\Events\Enumerable`, implemented in `Phalcon\Events\Manager`. [#17416](https://github.com/phalcon/cphalcon/issues/17416) [[doc]](https://docs.phalcon.io/5.18/events/)
- Added `candidatesFor()` to the `Phalcon\Contracts\ADR\Router\Router` contract and to `Phalcon\ADR\Router\Router`. It returns every Action class the convention router would try for a given HTTP method and path, in the order it tries them. [#17403](https://github.com/phalcon/cphalcon/issues/17403) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Added `classFor()` to the `Phalcon\Contracts\ADR\Router\Router` contract and to `Phalcon\ADR\Router\Router`. It names the Action class the convention would use for a static path. [#17410](https://github.com/phalcon/cphalcon/issues/17410) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Added `methodFor()` to the `Phalcon\Contracts\ADR\Router\Router` contract and to `Phalcon\ADR\Router\Router`. It names the HTTP method an Action class answers, the counterpart to `pathFor()`. [#17416](https://github.com/phalcon/cphalcon/issues/17416) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Added `pathFor()`, `setActionDirectory()` and `setWordSeparator()` to the `Phalcon\Contracts\ADR\Router\Router` contract and to `Phalcon\ADR\Router\Router`; the two setters are also on `Phalcon\ADR\Application`. `pathFor()` is the inverse of the routing convention, returning the canonical path an Action answers or `null`. Added `Phalcon\ADR\Exceptions\ActionDirectoryNotSet`. [#17405](https://github.com/phalcon/cphalcon/issues/17405) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Added eager loading of model relations: `Phalcon\Mvc\Model::find()` accepts an `eager` parameter - an array of dot-delimited relation paths, optionally `path => options` - which pre-loads the named relations with one query per relation instead of one per record. `Phalcon\Mvc\Model\Criteria::eager()` exposes the same on the criteria surface. [#17331](https://github.com/phalcon/cphalcon/issues/17331) [[doc]](https://docs.phalcon.io/5.18/db-models-relationships/)
- Added opt-in route-parameter pre-filtering to the ADR convention router via the new `Phalcon\ADR\Router\AttributeFilter`. An Action that declares a static `params()` method has its positional route segments validated against a regex, cast to a scalar type (`int`, `float`, `string`) and optionally passed through a converter closure, then written to the request as named attributes - all before the Action runs. A regex miss is treated as a route miss (404). [#17393](https://github.com/phalcon/cphalcon/issues/17393) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Added request attributes support to `Phalcon\Http\Request`. `Phalcon\Http\Request::getAttributes()` returns a `Phalcon\Http\Request\Bag\AttributeBag`, a mutable, string-keyed bag of arbitrary application-defined values attached to the request during its lifecycle (router, dispatcher, security components etc.). Writing with a `null` key (the `$bag[] = ...` append form) throws the new `Phalcon\Http\Request\Exceptions\NullKeyException`, since bag elements are always string-keyed. [#17367](https://github.com/phalcon/cphalcon/issues/17367) [[doc]](https://docs.phalcon.io/5.18/http-request/)
- Added the Action-Domain-Responder (ADR) HTTP stack under `Phalcon\ADR`, an alternative to MVC that splits request handling into three focused roles: an _Action_ (one invokable class per route) drives a _Domain_ (your business logic, which returns a `Phalcon\ADR\Payload\Payload` and never touches HTTP) and hands the result to a _Responder_ that turns it into a response.
  - `Phalcon\ADR\Container`: registrations for the `Phalcon\Container\Container`
  - `Phalcon\ADR\Emitter` : `SapiEmitter` to emit response data
  - `Phalcon\ADR\Events` : events wired to the Events Manager
  - `Phalcon\ADR\Exceptions` : granular exceptions the namespace throws
  - `Phalcon\ADR\Front` : One-command class wiring the application and executing it
  - `Phalcon\ADR\Input` : Bag of request data to be passed to the domain
  - `Phalcon\ADR\Middleware` : Onion based middleware stack
  - `Phalcon\ADR\Payload` : VO for transferring data from the domain and the responder
  - `Phalcon\ADR\Responder` : Chainable responders, handling redirect, json, text, error etc.
  - `Phalcon\ADR\Router` : Router for the application. [#17341](https://github.com/phalcon/cphalcon/issues/17341) [[doc]](https://docs.phalcon.io/5.18/adr/)
- ~~Added `Phalcon\Events\Manager::getEventTypes()`, returning the event types that currently have at least one listener attached, including those contributed by subscribers. [#17406](https://github.com/phalcon/cphalcon/issues/17406) [[doc]](https://docs.phalcon.io/5.18/events/)~~

### Fixed

- Fixed `Phalcon\ADR\Router\Router` treating `-` and `_` as the same delimiter, so `/forgot-password` and `/forgot_password` resolved to the same Action and the path-to-class map had no inverse. A single separator now applies in both directions, default `-` and settable with `setWordSeparator()`; `_` is literal. [#17405](https://github.com/phalcon/cphalcon/issues/17405) [[doc]](https://docs.phalcon.io/5.18/adr/)
- Fixed `Phalcon\Container\Definition\ServiceDefinition::resolveArgs()` treating any constructor-argument object that merely exposes a `resolve()` method as a lazy value, using `method_exists()`. Because `Phalcon\Container\Container` defines a private `resolve()`, autowiring a service whose constructor receives the container - i.e. `__construct(?Container $container)` - mistook the injected container for a lazy resolvable. The lazy check is now guarded for non-object arguments. [#17391](https://github.com/phalcon/cphalcon/issues/17391)
- Fixed `Phalcon\Db\Adapter\Pdo\Mysql::describeColumns()` returning string, date and time defaults still wrapped in quotes on MariaDB, e.g. `'fi'` instead of `fi`. MariaDB reports `COLUMN_DEFAULT` as the DDL source rather than the resolved literal. Expression defaults are unaffected. Note that on MariaDB this changes what `Phalcon\Db\Column::getDefault()` returns and what the model layer writes into an unset `NOT NULL` column on save; if you were stripping the quotes yourself, remove that workaround. MySQL is unaffected. [#17417](https://github.com/phalcon/cphalcon/issues/17417) [[doc]](https://docs.phalcon.io/5.18/db-layer/)
- Fixed `Phalcon\Db\Result\PdoResult::numRows()` failing on multi-line statements. [#17399](https://github.com/phalcon/cphalcon/issues/17399)
- Fixed `Phalcon\Http\Response\Cookies::delete()` not deleting a cookie that was not set in the same request; it now falls back to the `$_COOKIE` superglobal. `Phalcon\Http\Cookie::delete()` expires the cookie with its stored `path` and `domain` instead of the defaults. [#17395](https://github.com/phalcon/cphalcon/issues/17395) [[doc]](https://docs.phalcon.io/5.18/http-response/)
- Fixed `Phalcon\Mvc\Model::find()` and `Phalcon\Mvc\Model::findFirst()` raising an error `Call to undefined method static::getpreparedquery()`. Using `self` vs. 'static` for the internal calls. [#17409](https://github.com/phalcon/cphalcon/issues/17409) [[doc]](https://docs.phalcon.io/5.18/db-models/)
- Fixed `Phalcon\Mvc\Model\Resultset::seek()` leaving the previous row in place as the current one when seeking past the end of a resultset held in memory. [#17399](https://github.com/phalcon/cphalcon/issues/17399) [[doc]](https://docs.phalcon.io/5.18/db-models/)
- Fixed `Phalcon\Mvc\Model\Resultset` costing two statements for every resultset on SQLite. [#17399](https://github.com/phalcon/cphalcon/issues/17399) [[doc]](https://docs.phalcon.io/5.18/db-models/)
- Fixed `Phalcon\Mvc\Model` ignoring attributes registered with `skipAttributes()`, `skipAttributesOnCreate()` and `skipAttributesOnUpdate()`, so a skipped column was emitted in the generated `INSERT`/`UPDATE` (breaking, for instance, inserts into a table with a MySQL generated column). The skip list is keyed with `null` values, and `isset()` on an array offset now follows PHP semantics (key present *and* value not `null`), which made every skipped attribute read as not registered; the checks in `doLowInsert()`, `doLowUpdate()` and the not-null validation now use `array_key_exists()`. [#17382](https://github.com/phalcon/cphalcon/issues/17382) [[doc]](https://docs.phalcon.io/5.18/db-models/)
- Fixed `Phalcon\Mvc\Model` inserting a literal `null` for a column the database can supply a value for, instead of the `DEFAULT` keyword (or omitting the column on an adapter without `DEFAULT` support, such as SQLite). This restores inserts against a MySQL `GENERATED ALWAYS AS (...) STORED` column, which rejects an explicit `null` with `SQLSTATE[HY000]: General error: 3105` but accepts `DEFAULT`. [#17382](https://github.com/phalcon/cphalcon/issues/17382) [[doc]](https://docs.phalcon.io/5.18/db-models/)

### Removed

- Removed `.php_cs.dist`, superseded by `resources/php-cs-fixer.php`. [#17419](https://github.com/phalcon/cphalcon/issues/17419)

## [5.17.0](https://github.com/phalcon/cphalcon/releases/tag/v5.17.0) (2026-07-17)

### Tools

- Zephir 1.1.0 (80d17e6a0)

### Changed

- Changed `Phalcon\Acl\Adapter\Memory` so a freshly constructed adapter returns an empty array instead of `null` from `getRoles()`, `getComponents()` and `getInheritedRoles()`. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/acl/)
- Changed the `Phalcon\Auth` array adapters (`Memory`, `Stream`) to compare non-password credential fields against configured row values as strings, so string input from a request (e.g. `'1'`) matches a typed row value (e.g. `1` or `true`) instead of failing a strict type comparison. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/auth/)
- Changed the `Phalcon\Auth` layer to throw granular `Phalcon\Auth\Exceptions\*` subclasses instead of the base `Phalcon\Auth\Exception`: `AccessNotRegistered`, `ActiveAccessRequired`, `DefaultGuardNotRegistered` and `GuardNotDefined` (`Phalcon\Auth\Manager`), `UnknownAdapter` and `UnknownGuard` (`Phalcon\Auth\ManagerFactory`), `OptionRequiresArray` and `OptionRequiresString` (`fromOptions()` option parsing), `SessionNamesMustDiffer` (`Phalcon\Auth\Guard\Config\SessionGuardConfig`), and `MissingHandlerContext` (`Phalcon\Auth\Access\Acl`). Each extends `Phalcon\Auth\Exception`, so existing `catch` blocks keep working. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/auth/)
- Deprecated `Phalcon\Acl\Adapter\Memory::getActiveKey()` (use `getActiveRole()`, `getActiveComponent()` and `getActiveAccess()`) and the legacy ACL interfaces `Phalcon\Acl\Adapter\AdapterInterface`, `Phalcon\Acl\RoleInterface`, `Phalcon\Acl\ComponentInterface`, `Phalcon\Acl\RoleAwareInterface` and `Phalcon\Acl\ComponentAwareInterface` in favour of their `Phalcon\Contracts\Acl\...` equivalents. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/acl/)
- Note: `Phalcon\Auth\ManagerFactory` validates the required guard configuration up front and throws a `Phalcon\Auth\Exception` subclass on a missing key, where earlier versions emitted a PHP notice followed by a `TypeError`; handlers that caught `TypeError` there should catch `Phalcon\Auth\Exception` instead. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/auth/)
- Refactored the read path of model hydration by extracting `Phalcon\Mvc\Model::cloneResultMapHydrate()` into the dedicated `Phalcon\Mvc\Model\Hydration\CloneResultMapHydrate` class and the case-insensitive column-map lookup into `Phalcon\Mvc\Model\Hydration\CaseInsensitiveColumnMap`. [#17228](https://github.com/phalcon/cphalcon/issues/17228) [[doc]](https://docs.phalcon.io/5.17/db-models/)
- `Phalcon\Support\Helper\Str\Concat::__invoke()` was reimplemented as a typed variadic `(string $delimiter, string ...$many)` - previously it declared no parameters and read them through `func_get_args()` - with unchanged behavior. [#17273](https://github.com/phalcon/cphalcon/issues/17273) [[doc]](https://docs.phalcon.io/5.17/traits)

### Added

- Added `@Connect`, `@Head`, `@Purge` and `@Trace` method annotations to `Phalcon\Mvc\Router\Annotations`, matching the existing `@Get`, `@Post`, `@Put`, `@Patch`, `@Delete` and `@Options` shortcuts. [[doc]](https://docs.phalcon.io/5.17/routing/)
- Added `Phalcon\Acl\Adapter\Storage`, a storage-backed ACL adapter that persists the entire policy as a versioned, serializer-agnostic snapshot to any `Phalcon\Storage` backend (Redis, Apcu, Stream, Memcached) and reloads it on construction, defined by the new `Phalcon\Contracts\Acl\Adapter\Persistable` contract (`save()`/`load()`). Callable (closure) rules are not serializable, so they are persisted as `DENY` (fail closed) and must be re-registered after `load()`; `load()` returns `false` for a snapshot without a version marker and throws `Phalcon\Acl\Exceptions\InvalidSnapshot` on an incompatible version or a malformed structure. Single-writer contract: `save()` writes the whole snapshot (last-write-wins; use external locking for concurrent writers). [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/acl/)
- Added `Phalcon\Acl\Exceptions\InvalidSnapshot`, thrown by `Phalcon\Acl\Adapter\Storage::load()` on an incompatible or malformed policy snapshot. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/acl/)
- Added `Phalcon\Encryption\Security\JWT\Validator::validateSubject()`, which compares the token's `sub` claim against the expected subject and reports `Validation: incorrect subject` on a mismatch. A `null` subject expresses no expectation and is skipped. [#17361](https://github.com/phalcon/cphalcon/issues/17361) [[doc]](https://docs.phalcon.io/5.17/encryption-security-jwt/)
- Added `Phalcon\Filter\Validation::setDefaultMessages()` and `Phalcon\Filter\Validation::getDefaultMessage()` for registering global default validator failure messages keyed by validator class name (e.g. `Validation::setDefaultMessages([PresenceOf::class => 'Default message :field is required'])`). A registered default overrides a validator's built-in class default message, while a message set on the validator instance (the constructor `message`/`template` option or `setTemplate()`) still takes precedence; it applies to validators whose message is produced through `getTemplate()`/`messageFactory()`. [#17257](https://github.com/phalcon/cphalcon/issues/17257) [[doc]](https://docs.phalcon.io/5.17/filter-validation/)
- Added `Phalcon\Filter\Validation\Validator\Files`, a validator for an array of uploaded files (`<input name="files[]" type="file" multiple>`). It accepts the same options as `Phalcon\Filter\Validation\Validator\File`, normalizes a single-file or transposed multi-file `$_FILES` node into individual files, and validates each by delegating to `File`, failing on the first file that violates a rule. [#17259](https://github.com/phalcon/cphalcon/issues/17259) [[doc]](https://docs.phalcon.io/5.17/filter-validation/)
- Added `Phalcon\Mvc\Model\Query::setResultsetRowClass()` and `Phalcon\Mvc\Model\Query::getResultsetRowClass()` to control the class used to hydrate rows that are not mapped to a model (custom-column `SELECT`s and joins). When set, those result rows are built as the given subclass of `Phalcon\Mvc\Model\Row` instead of `Row` itself - on both the simple (custom columns) and complex (join) resultset paths - so they can carry reusable helper methods. The class is validated when set (it must exist and be a subclass of `Phalcon\Mvc\Model\Row`), throwing the new `Phalcon\Mvc\Model\Query\Exceptions\ResultsetRowClassNotFound` or `Phalcon\Mvc\Model\Query\Exceptions\InvalidResultsetRowClass`. [#17337](https://github.com/phalcon/cphalcon/issues/17337) [[doc]](https://docs.phalcon.io/5.17/db-models/)
- Added `Phalcon\Mvc\Model\Query\Builder::setResultsetRowClass()` and `Phalcon\Mvc\Model\Query\Builder::getResultsetRowClass()` so the custom resultset row class can be set on a query builder, which forwards it to the `Phalcon\Mvc\Model\Query` it produces in `getQuery()`. Because `Phalcon\Paginator\Adapter\QueryBuilder` builds its query through the builder, `paginate()` now returns the given `Phalcon\Mvc\Model\Row` subclass for its non-model result rows. [#17337](https://github.com/phalcon/cphalcon/issues/17337) [[doc]](https://docs.phalcon.io/5.17/db-models/)
- Added a set of reusable traits under the `Phalcon\Traits` namespace that framework classes compose instead of carrying duplicated logic. The public traits:
    - `Phalcon\Traits\Support\Helper\Arr\GetTrait` - read an array element by key with a default value and an optional cast.
    - `Phalcon\Traits\Support\Helper\Arr\FilterTrait` - filter a collection with `array_filter()` and an optional callable.
    - `Phalcon\Traits\Support\Helper\Str\DirFromFileTrait` - build a nested directory path from a file name, with an optional path-safety flag.
    - `Phalcon\Traits\Support\Helper\Str\DirSeparatorTrait` - ensure a directory string ends with exactly one `DIRECTORY_SEPARATOR`.
    - `Phalcon\Traits\Support\Helper\Str\EndsWithTrait` / `StartsWithTrait` - case-insensitive, multibyte-aware suffix/prefix checks.
    - `Phalcon\Traits\Support\Helper\Str\InterpolateTrait` - PSR-3 `%placeholder%` message interpolation.
    - `Phalcon\Traits\Support\Helper\Str\CamelizeTrait` / `UncamelizeTrait` - convert between `CamelCase` and delimited strings.
    - `Phalcon\Traits\Support\Helper\Str\LowerTrait` / `UpperTrait` - multibyte-safe lower/upper casing.
    - `Phalcon\Traits\Support\Helper\Json\EncodeTrait` / `DecodeTrait` - `json_encode()`/`json_decode()` wrappers that throw the native `\JsonException`.
    - `Phalcon\Traits\Php\ApcuTrait` - wrappers for `apcu_*` methods.
    - `Phalcon\Traits\Php\Base64Trait` - wrappers for `base64_*` methods.
    - `Phalcon\Traits\Php\FileTrait` - wrappers for `file_*` methods.
    - `Phalcon\Traits\Php\HashTrait` - wrappers for `hash_*` methods.
    - `Phalcon\Traits\Php\HeaderTrait` - wrapper for `headers_sent`.
    - `Phalcon\Traits\Php\IgbinaryTrait` - wrappers for `igbinary_*` methods.
    - `Phalcon\Traits\Php\InfoTrait` - wrappers for `extension_loaded` and `function_exists.
    - `Phalcon\Traits\Php\IniTrait` - wrappers for `ini_*` methods.
    - `Phalcon\Traits\Php\MbCaseTrait` - wrappers for `mb_case_*` methods.
    - `Phalcon\Traits\Php\MsgpackTrait` - wrappers for `msgpack_*` methods.
    - `Phalcon\Traits\Php\OpensslTrait` - wrappers for `openssl_*` methods.
    - `Phalcon\Traits\Php\SerializeTrait` - wrappers for `serialize` and `unserialize`.
    - `Phalcon\Traits\Php\UrlTrait` - wrappers for `parse_url`, `rawurldecode` and `rawurlencode`.
    - `Phalcon\Traits\Php\YamlTrait` - - wrappers for `yaml_*` methods. These calls can be substituted in tests. [#17273](https://github.com/phalcon/cphalcon/issues/17273) [[doc]](https://docs.phalcon.io/5.17/traits)
- Added an opt-in "sticky" read/write connection mode to `Phalcon\Mvc\Model\Manager`. After `Phalcon\Mvc\Model\Manager::setSticky(true)`, once a model has written to its write connection during the current request cycle, any further reads for that write service are served from the write connection, so data written earlier in the request can be read back immediately. Writes are recorded via the new `Phalcon\Mvc\Model\Manager::registerWrite()` (called internally on a successful insert/update/delete), and `Phalcon\Mvc\Model\Manager::resetConnectionState()` clears the per-request tracking for long-running runtimes (e.g. Swoole, RoadRunner) that reuse the manager across requests. Sticky is off by default, preserving the existing read/write split; the transaction connection still takes precedence. The three methods are added to `Phalcon\Mvc\Model\ManagerInterface`. [#17256](https://github.com/phalcon/cphalcon/issues/17256) [[doc]](https://docs.phalcon.io/5.17/db-models/)
- Added an opt-in `allowWildcards` option to `Phalcon\Filter\Validation\Validator\File\MimeType` (and passed through from `Phalcon\Filter\Validation\Validator\File` via the same key) that, when `true`, matches each configured type against the detected MIME type as an anchored regular expression (e.g. `image/.*`, `video/.*`) in addition to an exact string match; it defaults to `false`, preserving the existing exact-match behavior. [#17254](https://github.com/phalcon/cphalcon/issues/17254) [[doc]](https://docs.phalcon.io/5.17/filter-validation/)
- Added closure binding to `Phalcon\Filter\Validation\Validator\Callback`: a `Closure` passed as the `callback` option is now bound to the validator instance (`$this`) before it is invoked, so the callback can call the validator's own public methods - for example `$this->setTemplate('...')` to set a per-failure message from inside the callback. Non-closure callables (function-name strings, `[object, method]` arrays) are left unbound, and the callback's return contract is unchanged (`bool`, or a `Phalcon\Filter\Validation\ValidatorInterface` to delegate). [#17255](https://github.com/phalcon/cphalcon/issues/17255) [[doc]](https://docs.phalcon.io/5.17/filter-validation/)
- Added support for absolute and current-template-relative paths in the Volt `{% extends %}` and `{% include %}` tags (`Phalcon\Mvc\View\Engine\Volt\Compiler`): an absolute path (Unix `/...`, or a Windows drive/UNC path) is now used as-is, and a path beginning with `./` or `../` is resolved against the directory of the template currently being compiled instead of the views directory; every other path keeps the existing views-directory-relative resolution. [#17269](https://github.com/phalcon/cphalcon/issues/17269) [[doc]](https://docs.phalcon.io/5.17/volt/)
- Added the `Phalcon\Contracts\Acl` contracts - `Phalcon\Contracts\Acl\Adapter\Adapter`, `Phalcon\Contracts\Acl\Adapter\Persistable`, `Phalcon\Contracts\Acl\Role`, `Phalcon\Contracts\Acl\Component`, `Phalcon\Contracts\Acl\RoleAware` and `Phalcon\Contracts\Acl\ComponentAware` - as the canonical homes for the ACL interfaces; the legacy `Phalcon\Acl\...\*Interface` types remain as deprecated bridges that extend them. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/acl/)
- Added the `Phalcon\Contracts\Queue\Inspectable` contract (`getStats(Queue $queue): array`), implemented by `Phalcon\Queue\Adapter\Beanstalk\BeanstalkContext`, exposing the Beanstalkd `stats-tube` fields (`current-jobs-ready`, `current-jobs-reserved`, `current-jobs-delayed`, `current-jobs-buried`, `current-jobs-urgent`, `total-jobs`, the `cmd-*` counters, ...) for queue backlog/depth monitoring. The returned array is adapter-native; the `current-jobs-*` keys are always present (zero for an unknown tube) and the read runs on its own short-lived connection. Backed by a new `Phalcon\Queue\Adapter\Beanstalk\BeanstalkConnection::statsTube()` wire command. [#17209](https://github.com/phalcon/cphalcon/issues/17209) [[doc]](https://docs.phalcon.io/5.17/queue/)
- Added the granular `Phalcon\Auth\Exceptions\*` exceptions `AccessNotRegistered`, `ActiveAccessRequired`, `DefaultGuardNotRegistered`, `GuardNotDefined`, `MissingHandlerContext`, `OptionRequiresArray`, `OptionRequiresString`, `SessionNamesMustDiffer`, `UnknownAdapter` and `UnknownGuard`. [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/auth/)

### Fixed

- Fixed `Phalcon\Auth` login timing leaking account existence: the credential adapters now perform a throwaway password hash on the user-not-found path, so an attempt for an unknown identifier costs the same as one for a real account with a wrong password (mitigates login-timing user enumeration). [#17220](https://github.com/phalcon/cphalcon/issues/17220) [[doc]](https://docs.phalcon.io/5.17/auth/)
- Fixed `Phalcon\Db\Dialect::getSqlExpression()` throwing `The argument is not initialized or iterable()` while resolving a `case` expression when the expression array is held as a PHP reference, by fetching the `when-clauses` list through `array_values()` before iterating it. [#17225](https://github.com/phalcon/cphalcon/issues/17225) [[doc]](https://docs.phalcon.io/5.17/db-layer/)
- Fixed `Phalcon\Encryption\Crypt::encrypt()` to properly capture exceptions thrown by `openssl_random_pseudo_bytes`. [#17326](https://github.com/phalcon/cphalcon/issues/17326) [[doc]](https://docs.phalcon.io/5.17/encryption/)
- Fixed `Phalcon\Encryption\Security\JWT\Token\Token::validate()` throwing `Phalcon\Encryption\Security\JWT\Exceptions\InvalidAudienceType` when handed a freshly constructed `Phalcon\Encryption\Security\JWT\Validator`, which made a default `Validator` impossible to pass to it. [#17361](https://github.com/phalcon/cphalcon/issues/17361) [[doc]](https://docs.phalcon.io/5.17/encryption-security-jwt/)
- Fixed `Phalcon\Encryption\Security\JWT\Validator::validateIssuedAt()` and `Phalcon\Encryption\Security\JWT\Validator::validateNotBefore()` rejecting a token whose `iat`/`nbf` claim falls on exactly the validated timestamp. [#17361](https://github.com/phalcon/cphalcon/issues/17361) [[doc]](https://docs.phalcon.io/5.17/encryption-security-jwt/)
- Fixed `Phalcon\Forms\Element\AbstractElement::render()` to cast a non-`null` element value to `string` before passing it to the input helper, so a numeric default set via `setDefault()` (e.g. `setDefault(10)` or `setDefault(10.5)` on a `Phalcon\Forms\Element\Numeric`) renders as `value="10"` instead of raising a `TypeError` for passing an `int`/`float` to the helper's `string` `$value` parameter. [#17232](https://github.com/phalcon/cphalcon/issues/17232) [[doc]](https://docs.phalcon.io/5.17/forms/)
- Fixed `Phalcon\Http\Response::getStatusCode()` and `Phalcon\Http\Response::getReasonPhrase()` raising a `TypeError` (`substr(): Argument #1 ($string) must be of type string, bool given`) when no `Status` header had been set (e.g. a response built with only `setContent()`), because `Phalcon\Http\Response\Headers::get('Status')` returns `false` for an absent header; the header value is now cast to string before `substr()`, so both methods return `null` as documented. [#17248](https://github.com/phalcon/cphalcon/issues/17248) [[doc]](https://docs.phalcon.io/5.17/http-response/)
- Fixed `Phalcon\Image\Adapter\AbstractAdapter::resize()` truncating the scaled master-mode (`Enum::WIDTH`, `Enum::HEIGHT`, `Enum::PRECISE`) dimension to an `int` before rounding, so a value whose fractional part was `>= 0.5` came out one pixel short (e.g. a `1820x694` source resized to height `80` produced width `209` instead of `210`); the scaled width/height are now rounded before the integer cast. [#17225](https://github.com/phalcon/cphalcon/issues/17225) [[doc]](https://docs.phalcon.io/5.17/image/)
- Fixed `Phalcon\Mvc\Model::cloneResult()` and `Phalcon\Mvc\Model::cloneResultMap()` calling a model setter (or throwing `Phalcon\Mvc\Model\Exceptions\PropertyNotAccessible` when no setter exists) while hydrating a declared `private` property, because the property write from `Model` scope fell back to `__set()` and its `possibleSetter()` routing regardless of the `orm.call_setters_on_hydration` setting. [#16454](https://github.com/phalcon/cphalcon/issues/16454) [[doc]](https://docs.phalcon.io/5.17/db-models/)
- Fixed `Phalcon\Mvc\Model::create()` and `Phalcon\Mvc\Model::update()` passing `null` to the `field` argument of `Phalcon\Messages\Message` (typed `string` since v5.14), which raised a `Passing null to parameter #2 ($field) of type string is deprecated` warning when calling `create()` on an existing record or `update()` on a non-existent one; they now pass an empty string. [#17224](https://github.com/phalcon/cphalcon/issues/17224) [[doc]](https://docs.phalcon.io/5.17/db-models/)
- Fixed `Phalcon\Mvc\Model\Query::executeUpdate()` raising a PDO `Invalid parameter number: mixed named and positional parameters` error for a PHQL `UPDATE` whose `SET` clause is an expression carrying a bound placeholder (e.g. `SET col = col + :inc:`): the named placeholder is now resolved from the bind parameters and inlined into the expression before the `Phalcon\Db\RawValue` is built, so it no longer collides with the positional `?` marker of the primary-key `WHERE` clause, and the placeholder is removed from the bind parameters forwarded to the pre-update `SELECT`. [#16976](https://github.com/phalcon/cphalcon/issues/16976) [[doc]](https://docs.phalcon.io/5.17/db-models/)
- Fixed a segmentation fault when rendering a Volt template that `extends` a parent chain but defines no blocks of its own, where a block declared higher in the chain calls `{{ partial() }}`: `Phalcon\Mvc\View\Engine\Volt\Compiler::compileSource()` passed a `null` `blocks` value to `array_key_exists()`, which read it as an array from an uninitialized pointer; `blocks` is now coerced to an empty array so any inheritance depth is handled. [#17294](https://github.com/phalcon/cphalcon/issues/17294) [[doc]](https://docs.phalcon.io/5.17/volt/)
- Fixed the PHP 8.4/8.5 deprecation notices raised by the extension: removed the `imagedestroy()` calls in `Phalcon\Image\Adapter\Gd` (a no-op since PHP 8.0), the `finfo_close()` calls in `Phalcon\Http\Request\File` and `Phalcon\Filter\Validation\Validator\File\MimeType` and the `ReflectionProperty::setAccessible()` call in `Phalcon\Support\Debug\Dump` (no-ops since PHP 8.1), clamped the random pad byte in `Phalcon\Encryption\Crypt\Padding\Iso10126` to `chr(rand() % 256)` to avoid the out-of-range `chr()` deprecation on PHP 8.5, and guarded `Phalcon\Messages\Messages::offsetSet()` against an implicit `null` array offset. [#17253](https://github.com/phalcon/cphalcon/issues/17253) [[doc]](https://docs.phalcon.io/5.17/)

### Removed

- Removed the deprecated `Serializable` interface from `Phalcon\Mvc\Model` and `Phalcon\Mvc\Model\Resultset` (deprecated by PHP 8.1); the `__serialize()` and `__unserialize()` magic methods remain, so model and resultset serialization is unchanged. [#17253](https://github.com/phalcon/cphalcon/issues/17253) [[doc]](https://docs.phalcon.io/5.17/db-models/)

## [5.16.0](https://github.com/phalcon/cphalcon/releases/tag/v5.16.0) (2026-06-22)

### Tools

- Zephir Parser v2.0.4
- Zephir 0.23.0 (development - f87b27ba9)

### Changed

- Changed `Phalcon\Support\Debug::getVersion()` to return a compact version badge anchor (`v<version>`) instead of the previous "Phalcon Framework" version block. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)
- Changed `Phalcon\Support\Debug` and `Phalcon\Support\Debug\Dump` to render from named, overridable template strings (the new `Phalcon\Contracts\Support\Debug\TemplateAware` contract with `getTemplate()`/`setTemplate()`) filled by `strtr`, instead of inline string concatenation. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)
- Changed `Phalcon\Support\Debug` into a thin coordinator that delegates exception-data collection to the new `Phalcon\Support\Debug\ReportBuilder` and HTML rendering to a `Phalcon\Contracts\Support\Debug\Renderer` (default `Phalcon\Support\Debug\Renderer\HtmlRenderer`), and exposes `getRenderer()`/`setRenderer()` to swap the renderer. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)
- Changed the `Phalcon\Support\Debug` Memory panel to report both real and peak memory usage. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)
- Changed the `Phalcon\Support\Debug` exception page to a redesigned, asset-driven layout (masthead with the Phalcon logo, error card, tabbed Request/Server/Included Files/Memory/Variables context, and collapsible backtrace frames); `getCssSources()` and `getJsSources()` now reference a single `debug.css` and `debug.js` instead of the bundled jQuery, jQuery-UI and prettify assets. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)

### Added

- Added `Phalcon\Support\Debug::getRenderer()` and `Phalcon\Support\Debug::setRenderer()`. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)
- Added connection-liveness and opt-in auto-reconnect support to `Phalcon\Db\Adapter\Pdo\AbstractPdo`: `ping()` (a `SELECT 1` probe), `ensureConnection()` (reconnect in place when the probe fails), and `setAutoReconnect()`/`getAutoReconnect()` (also settable via the `autoReconnect` descriptor key). When auto-reconnect is enabled and a query fails on a lost ("gone away") connection outside a transaction, `execute()` and `query()` fire the new `db:connectionLost` event, reconnect, and retry the statement once; a loss inside a transaction is re-thrown without retry. "Gone away" detection is provided per driver by `Phalcon\Db\Adapter\Pdo\Mysql` (error codes 2006/2013) and `Phalcon\Db\Adapter\Pdo\Postgresql` (SQLSTATE 08003/08006/57P01-03), with a message fallback. [#17204](https://github.com/phalcon/cphalcon/issues/17204) [[doc]](https://docs.phalcon.io/5.16/db-layer/)
- Added the Beanstalk queue adapter (`Phalcon\Queue\Adapter\Beanstalk\*`) over a dependency-free socket client, with native delivery delay and priority and a `VisibilityAware` consumer (`touch()`). [#17051](https://github.com/phalcon/cphalcon/issues/17051) [[doc]](https://docs.phalcon.io/5.16/queue/)
- Added the Memory and Stream queue adapters (`Phalcon\Queue\Adapter\Memory\*`, in-process FIFO; `Phalcon\Queue\Adapter\Stream\*`, file-per-queue with `flock`). [#17051](https://github.com/phalcon/cphalcon/issues/17051) [[doc]](https://docs.phalcon.io/5.16/queue/)
- Added the Redis queue adapter (`Phalcon\Queue\Adapter\Redis\*`) with list-backed FIFO delivery (`LPUSH`/`BRPOP`), sorted-set delivery delay and native blocking receive. [#17051](https://github.com/phalcon/cphalcon/issues/17051) [[doc]](https://docs.phalcon.io/5.16/queue/)
- Added the `Phalcon\Contracts\Support\Debug\TemplateAware` and `Phalcon\Contracts\Support\Debug\Renderer` contracts, the `Phalcon\Support\Debug\ReportBuilder` and `Phalcon\Support\Debug\Renderer\HtmlRenderer` classes, and the value objects `Phalcon\Support\Debug\Report\ExceptionReport` and `Phalcon\Support\Debug\Report\BacktraceItem`. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)
- Added the `Phalcon\Queue\AdapterFactory` and `Phalcon\Queue\QueueFactory` factories, and registered the `queueFactory` service in `Phalcon\Di\FactoryDefault` and `Phalcon\Di\FactoryDefault\Cli`. [#17051](https://github.com/phalcon/cphalcon/issues/17051) [[doc]](https://docs.phalcon.io/5.16/queue/)
- Added the `Phalcon\Queue` component, a first-class queue/messaging layer modeled on the queue-interop contracts, with the `Phalcon\Contracts\Queue\*` interfaces (`ConnectionFactory`, `Context`, `Destination`, `Queue`, `Topic`, `Producer`, `Consumer`, `SubscriptionConsumer`, `Message`, `Processor`, `VisibilityAware`) and the `Phalcon\Queue\Exceptions\*` hierarchy (`QueueThrowable`, `Exception` and the typed `Invalid*` / `*NotSupportedException` exceptions). [#17051](https://github.com/phalcon/cphalcon/issues/17051) [[doc]](https://docs.phalcon.io/5.16/queue/)
- Added the queue consumer runner (`Phalcon\Queue\Consumer\QueueConsumer`, `Worker`, `WorkerOptions`, `BoundProcessor`, `Events`) and the CLI consumer task `Phalcon\Queue\Cli\ConsumerTask`. [#17051](https://github.com/phalcon/cphalcon/issues/17051) [[doc]](https://docs.phalcon.io/5.16/queue/)
- Added the same liveness and opt-in auto-reconnect support to `Phalcon\DataMapper\Pdo\Connection` (`ping()`, `ensureConnection()`, `setAutoReconnect()`/`getAutoReconnect()`), wrapping `exec()`, `perform()`, `prepare()`, and `query()` with the single-retry behavior. This connection has no events manager, so no `db:connectionLost` event is fired; "gone away" detection is driver-agnostic and the in-transaction guard uses a locally tracked transaction level. [#17204](https://github.com/phalcon/cphalcon/issues/17204) [[doc]](https://docs.phalcon.io/5.16/db-layer/)

### Fixed

- Fixed `Phalcon\Mvc\Model::cloneResultMap()` calling model setters during ORM hydration unconditionally (introduced in 5.12.0 via [#14810](https://github.com/phalcon/cphalcon/issues/14810)), which ran user setters on every record hydrated by `find()`/`findFirst()`; a setter that issued an ORM query (e.g. `self::findFirstByEmail()`) recursed infinitely, as `findFirst()` re-entered `cloneResultMap()`, which re-invoked the setter, which called `findFirst()` again. Hydration setters are now gated by a dedicated `orm.call_setters_on_hydration` setting (default `false`), decoupled from `orm.disable_assign_setters` (which still governs `assign()`); this restores the pre-5.12.0 hydration behavior by default and makes setter execution during hydration opt-in. [#17214](https://github.com/phalcon/cphalcon/issues/17214) [[doc]](https://docs.phalcon.io/5.16/db-models/)
- Fixed `Phalcon\Mvc\Router\Route::compilePattern()` and `Phalcon\Cli\Router\Route::compilePattern()` expanding the `:params` placeholder - and the built-in `/:controller/:action/:params` and `/:task/:action/:params` default routes - to the nested quantifier `(/.*)*`. The group body overlaps itself, so an unmatchable trailing byte made the compiled pattern backtrack catastrophically: a short crafted URI (a run of `/` followed by a byte `.` cannot match) drove the `preg_match()` in `Phalcon\Mvc\Router::handle()` / `Phalcon\Cli\Router::handle()` into exponential time on every request. The trailing group is now compiled to the equivalent `(/.*)?`, which captures the same `params` value in linear time. [[doc]](https://docs.phalcon.io/5.16/routing/)
- Fixed `Phalcon\Support\Debug` ignoring the `request` entry of `setBlacklist()`: `$_REQUEST` is now filtered against the `request` blacklist, where previously both superglobals were filtered against the `server` blacklist only. [#17202](https://github.com/phalcon/cphalcon/issues/17202) [[doc]](https://docs.phalcon.io/5.16/support-debug/)
- Fixed `Phalcon\Tag\Select::selectField()` to invoke the resultset `using` render callback only when it is a `Closure` (previously any object), keeping the dynamically invoked callable out of reach of user-controlled data. [#17210](https://github.com/phalcon/cphalcon/issues/17210)

### Removed

## [5.15.0](https://github.com/phalcon/cphalcon/releases/tag/v5.15.0) (2026-06-18)

### Tools

- Zephir Parser v2.0.4
- Zephir 0.23.0 (development - 27535f802)

### Changed

- Changed `Phalcon\Acl\Adapter\Memory` to fire the `acl:beforeCheckAccess` and `acl:afterCheckAccess` events with an immutable array payload (`role`, `component`, `access`, plus `granted` on the `after` event) as the event data, instead of passing the adapter instance. [#17143](https://github.com/phalcon/cphalcon/issues/17143) [[doc]](https://docs.phalcon.io/5.15/acl/)
- Changed `Phalcon\Assets\AssetInterface` and `Phalcon\Assets\FilterInterface` to extend the new `Phalcon\Contracts\Assets\Asset` and `Phalcon\Contracts\Assets\Filter` contracts. [#17147](https://github.com/phalcon/cphalcon/issues/17147) [[doc]](https://docs.phalcon.io/5.15/assets/)
- Changed `Phalcon\Assets\Collection::has()` to an O(1) keyed lookup (`isset`) over the asset store instead of a linear scan. [#17147](https://github.com/phalcon/cphalcon/issues/17147) [[doc]](https://docs.phalcon.io/5.15/assets/)
- Changed `Phalcon\Assets\Manager` to reject any collection filter that is not a `Phalcon\Assets\FilterInterface` instance with `Phalcon\Assets\Exceptions\InvalidFilter`, instead of a fatal error when a non-conforming object reached `filter()`. [#17147](https://github.com/phalcon/cphalcon/issues/17147) [[doc]](https://docs.phalcon.io/5.15/assets/)
- Changed `Phalcon\Auth\Guard\Session` to accept an optional `Phalcon\Time\Clock\ClockInterface`; the remember-me cookie expiry now reads "now" through the clock, defaulting to `Phalcon\Time\Clock\SystemClock::fromUTC()`. [#17151](https://github.com/phalcon/cphalcon/issues/17151) [[doc]](https://docs.phalcon.io/5.15/auth/) [[doc]](https://docs.phalcon.io/5.15/time-clock/)
- Changed `Phalcon\Auth\Manager::attempt()` and `Phalcon\Auth\Manager::logout()` to throw `Phalcon\Auth\Exceptions\DoesNotImplement` (a subclass of `Phalcon\Auth\Exception`) when the default guard does not implement `GuardStateful`. [#17148](https://github.com/phalcon/cphalcon/issues/17148) [[doc]](https://docs.phalcon.io/5.15/auth/)
- Changed `Phalcon\Auth\ManagerFactory` to validate the required guard configuration keys (`type`, `adapter`, and the adapter `name`) up front, throwing a diagnostic `Phalcon\Auth\Exception` instead of a notice and `TypeError`. [#17148](https://github.com/phalcon/cphalcon/issues/17148) [[doc]](https://docs.phalcon.io/5.15/auth/)
- Changed `Phalcon\Autoload\Exceptions\LoaderDirectoriesNotArray` to accept an optional namespace name; when supplied, the message names the namespace whose directory registration failed. [#17149](https://github.com/phalcon/cphalcon/issues/17149) [[doc]](https://docs.phalcon.io/5.15/autoload/)
- Changed `Phalcon\Autoload\Loader::getFoundPath()` to return the resolved path for every lookup strategy (class map, namespace, directory), not only for file loading. [#17149](https://github.com/phalcon/cphalcon/issues/17149) [[doc]](https://docs.phalcon.io/5.15/autoload/)
- Changed `Phalcon\Autoload\Loader` to drop the redundant directory and namespace-prefix re-normalization on the namespace resolution path (no behavior change). [#17149](https://github.com/phalcon/cphalcon/issues/17149) [[doc]](https://docs.phalcon.io/5.15/autoload/)
- Changed `Phalcon\Cache\AbstractCache::checkKey()` and `Phalcon\Cache\AbstractCache::checkKeys()` to throw the exception class returned by `getExceptionClass()` (defaulting to `Phalcon\Cache\Exception\InvalidArgumentException`) instead of the dedicated `InvalidCacheKey` / `CacheKeysNotIterable` subclasses, so the thrown type can be overridden (for example to restore the PSR-16 marker). The exception messages are unchanged. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/cache/)
- Changed `Phalcon\Cache\AbstractCache::checkKey()` to reject an empty-string key, so every cache operation now throws for `""`, matching the PSR-16 requirement that keys be non-empty and closing the bare-prefix collision channel. [#17164](https://github.com/phalcon/cphalcon/issues/17164) [[doc]](https://docs.phalcon.io/5.15/cache/)
- Changed `Phalcon\Cache\AbstractCache::doDelete()` to validate the key before firing the `cache:beforeDelete` event, matching `doGet()`/`doSet()`/`doHas()`; the event no longer fires for a delete that throws on an invalid key. [#17164](https://github.com/phalcon/cphalcon/issues/17164) [[doc]](https://docs.phalcon.io/5.15/cache/)
- Changed `Phalcon\Cache\AbstractCache::doSetMultiple()` to validate every key before writing any item, so an invalid key fails the operation up front instead of leaving the already-written pairs persisted behind the thrown exception. [#17164](https://github.com/phalcon/cphalcon/issues/17164) [[doc]](https://docs.phalcon.io/5.15/cache/)
- Changed `Phalcon\Cache\CacheInterface` to extend the new `Phalcon\Contracts\Cache\Cache` contract. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/cache/)
- Changed `Phalcon\Cli\Router::handle()` to return the router instance on every path; the matched branch previously returned `null`. [#17165](https://github.com/phalcon/cphalcon/issues/17165) [[doc]](https://docs.phalcon.io/5.15/application-cli/)
- Changed `Phalcon\Cli\Router\Exceptions\BeforeMatchNotCallable`, `Phalcon\Cli\Router\Exceptions\InvalidRoutePaths`, and `Phalcon\Cli\Router\Exceptions\RouterArgumentsInvalidType` to accept optional context (the route pattern, the route pattern, and the received type respectively) and include it in the exception message. [#17165](https://github.com/phalcon/cphalcon/issues/17165) [[doc]](https://docs.phalcon.io/5.15/application-cli/)
- Changed `Phalcon\Cli\Router\Route::beforeMatch()` to reject a non-callable callback at registration with `Phalcon\Cli\Router\Exceptions\BeforeMatchNotCallable`, instead of deferring the failure to match time, matching the `Phalcon\Mvc\Router\Route` behavior. [#17165](https://github.com/phalcon/cphalcon/issues/17165) [[doc]](https://docs.phalcon.io/5.15/application-cli/)
- Changed `Phalcon\Contracts\Paginator\Adapter::setCurrentPage()` and `Phalcon\Contracts\Paginator\Adapter::setLimit()` to return the `Phalcon\Contracts\Paginator\Adapter` contract instead of the implementation-side `Phalcon\Paginator\Adapter\AdapterInterface`, removing the contract layer's dependency on the implementation namespace. Implementations returning the narrower interface remain covariant-compatible. [#17153](https://github.com/phalcon/cphalcon/issues/17153) [[doc]](https://docs.phalcon.io/5.15/db-pagination/)
- Changed `Phalcon\Contracts\Paginator\Repository` to document the offset and cursor adapter dialects on its getters: cursor adapters (`Phalcon\Paginator\Adapter\QueryBuilderCursor`) store cursor values in `getCurrent()`/`getNext()` and do not compute `getTotalItems()`, `getLast()` or `getPrevious()` (returning `0`). [#17153](https://github.com/phalcon/cphalcon/issues/17153) [[doc]](https://docs.phalcon.io/5.15/db-pagination/)
- Changed `Phalcon\Db\Adapter\AbstractAdapter::__construct()` to reject a `dialectClass` descriptor object that does not implement `Phalcon\Db\DialectInterface` with the new `Phalcon\Db\Exceptions\InvalidDialectClass` at construction time, instead of deferring the failure to first use. [#17163](https://github.com/phalcon/cphalcon/issues/17163) [[doc]](https://docs.phalcon.io/5.15/db-layer/)
- Changed `Phalcon\Db\Adapter\AbstractAdapter::tableExists()` and `Phalcon\Db\Adapter\AbstractAdapter::tableOptions()` to return `false` and `[]` respectively when the metadata query returns no rows, instead of emitting a notice on a missing array offset. [#17163](https://github.com/phalcon/cphalcon/issues/17163) [[doc]](https://docs.phalcon.io/5.15/db-layer/)
- Changed `Phalcon\Db\Geometry\GeometryInterface` to extend the new `Phalcon\Contracts\Db\Geometry\Geometry` contract. [#17163](https://github.com/phalcon/cphalcon/issues/17163) [[doc]](https://docs.phalcon.io/5.15/db-layer/)
- Changed `Phalcon\Dispatcher\AbstractDispatcher::callActionMethod()` to build the `dispatch:beforeCallAction`/`afterCallAction` observer `Phalcon\Support\Collection` directly instead of resolving it from the DI container by class name, removing a per-action container lookup on the hot path. (Release note: an application that registered a `Phalcon\Support\Collection` service to override that lookup is no longer consulted here.) [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Changed `Phalcon\Dispatcher\AbstractDispatcher::callActionMethod()` to re-validate the handler/action pair with `is_callable()` after the `dispatch:beforeCallAction` observer runs, so a listener that substitutes a non-existent action now fails through the `EXCEPTION_ACTION_NOT_FOUND` channel instead of a raw `call_user_func_array()` fatal. [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Changed `Phalcon\Dispatcher\AbstractDispatcher` to declare its `throwDispatchException()` and `handleException()` error hooks as `abstract protected` methods and call them directly instead of through dynamic string dispatch. A custom dispatcher that omits either now fails when the class is loaded rather than with an undefined-method fatal on the first error path. [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Changed `Phalcon\Dispatcher\AbstractDispatcher` to document its three lifecycle hook channels (events-manager listener, duck-typed handler method, and the `dispatch:beforeCallAction` observer), record the `afterBinding` blocks' intentional bypass of `handleException()`, and correct the stale initialize-ordering note that referenced Phalcon 4.0 (no behavior change). [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Changed `Phalcon\Dispatcher\DispatcherInterface`, `Phalcon\Mvc\DispatcherInterface`, and `Phalcon\Cli\DispatcherInterface` to extend the new `Phalcon\Contracts\Dispatcher\Dispatcher`, `Phalcon\Contracts\Mvc\Dispatcher`, and `Phalcon\Contracts\Cli\Dispatcher` contracts. [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Changed `Phalcon\Domain\Payload\PayloadInterface`, `Phalcon\Domain\Payload\ReadableInterface`, and `Phalcon\Domain\Payload\WriteableInterface` to extend the new `Phalcon\Contracts\Domain\Payload\Payload`, `Phalcon\Contracts\Domain\Payload\Readable`, and `Phalcon\Contracts\Domain\Payload\Writeable` contracts. [#17162](https://github.com/phalcon/cphalcon/issues/17162) [[doc]](https://docs.phalcon.io/5.15/domain/)
- Changed `Phalcon\Encryption\Crypt::decrypt()` to reject input shorter than the selected cipher requires up front with the new `Phalcon\Encryption\Crypt\Exception\InvalidDecryptLength`, instead of failing later as the less specific `Phalcon\Encryption\Crypt\Exception\DecryptionFailed`. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Changed `Phalcon\Encryption\Crypt::setAuthTagLength()` to validate that the length is between 4 and 16 bytes, throwing the new `Phalcon\Encryption\Crypt\Exception\InvalidAuthTagLength` instead of accepting any integer. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Changed `Phalcon\Encryption\Crypt\CryptInterface` and `Phalcon\Encryption\Crypt\Padding\PadInterface` to extend the new `Phalcon\Contracts\Encryption\Crypt\Crypt` and `Phalcon\Contracts\Encryption\Crypt\Padding\Pad` contracts. [#17162](https://github.com/phalcon/cphalcon/issues/17162) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Changed `Phalcon\Encryption\Crypt\PadFactory::padNumberToService()` to throw on an unknown padding constant instead of silently mapping it to the no-op padding (fail closed). [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Changed `Phalcon\Encryption\Crypt` and `Phalcon\Encryption\Crypt\CryptInterface` to drop the unreachable ECB padding branches (ECB ciphers are filtered out at construction) and to document the encrypted payload wire format (`iv ‖ hmac ‖ ciphertext ‖ tag`) and the AEAD parameter statefulness. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Changed `Phalcon\Encryption\Security\JWT\Signer\SignerInterface` to extend the new `Phalcon\Contracts\Encryption\Security\JWT\Signer\Signer` contract. [#17162](https://github.com/phalcon/cphalcon/issues/17162) [[doc]](https://docs.phalcon.io/5.15/encryption-security-jwt/)
- Changed `Phalcon\Encryption\Security\JWT\Validator` to accept an optional `Phalcon\Time\Clock\ClockInterface` constructor parameter for reading the current time; `timeShift` is retained as the legacy clock-skew mechanism. [#17151](https://github.com/phalcon/cphalcon/issues/17151) [[doc]](https://docs.phalcon.io/5.15/encryption-security/) [[doc]](https://docs.phalcon.io/5.15/time-clock/)
- Changed `Phalcon\Encryption\Security\Uuid\UuidInterface`, `Phalcon\Encryption\Security\Uuid\TimeBasedUuidInterface`, and `Phalcon\Encryption\Security\Uuid\NodeProviderInterface` to extend the new `Phalcon\Contracts\Encryption\Security\Uuid\Uuid`, `Phalcon\Contracts\Encryption\Security\Uuid\TimeBasedUuid`, and `Phalcon\Contracts\Encryption\Security\Uuid\NodeProvider` contracts. [#17162](https://github.com/phalcon/cphalcon/issues/17162) [[doc]](https://docs.phalcon.io/5.15/encryption-security/)
- Changed `Phalcon\Flash\AbstractFlash` to declare `message()` as an `abstract public` method and call it directly from `error()`, `notice()`, `success()`, and `warning()` instead of through dynamic string dispatch. [#17158](https://github.com/phalcon/cphalcon/issues/17158) [[doc]](https://docs.phalcon.io/5.15/flash/)
- Changed `Phalcon\Flash\FlashInterface` to extend the new `Phalcon\Contracts\Flash\Flash` contract. [#17158](https://github.com/phalcon/cphalcon/issues/17158) [[doc]](https://docs.phalcon.io/5.15/flash/)
- Changed `Phalcon\Flash\Session::output()` to no longer discard the accumulated messages when implicit flush is disabled; it now clears the buffer only in the implicit-flush (echo) mode, so with implicit flush off the rendered messages remain reachable instead of being silently destroyed. [#17158](https://github.com/phalcon/cphalcon/issues/17158) [[doc]](https://docs.phalcon.io/5.15/flash/)
- Changed `Phalcon\Html\Attributes::renderAttributes()` to escape attribute values through `Phalcon\Html\Escaper\AttributeEscaper` instead of a hardcoded `htmlspecialchars()` call, consolidating attribute escaping in one place. Output is byte-identical for the default configuration (`ENT_QUOTES`, UTF-8, double-encode on). [#17157](https://github.com/phalcon/cphalcon/issues/17157) [[doc]](https://docs.phalcon.io/5.15/html-attributes/)
- Changed `Phalcon\Html\Breadcrumbs::render()` to return an empty string when no crumbs have been added, instead of indexing `end([])` and emitting a PHP warning followed by a malformed single-crumb `<dl>`. [#17157](https://github.com/phalcon/cphalcon/issues/17157) [[doc]](https://docs.phalcon.io/5.15/html-breadcrumbs/)
- Changed `Phalcon\Html\Link\Serializer\Header::serialize()` to escape embedded backslashes and double quotes in attribute values per the RFC 8288 quoted-string rules, so an attribute value containing a double quote no longer produces a malformed `Link` header. [#17157](https://github.com/phalcon/cphalcon/issues/17157) [[doc]](https://docs.phalcon.io/5.15/html-link/)
- Changed `Phalcon\Image\Adapter\AbstractAdapter::background()` and `Phalcon\Image\Adapter\AbstractAdapter::text()` to validate the supplied hex color through a single shared parser, throwing the new `Phalcon\Image\Exceptions\InvalidColor` for a malformed color instead of silently producing incorrect channel values. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/image/)
- Changed `Phalcon\Image\Adapter\AbstractAdapter` to declare its fourteen `process*` operations as `abstract protected` methods and call them directly instead of through dynamic string dispatch. A custom adapter that omits one now fails when the class is loaded rather than with an undefined-method fatal on the first operation. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/image/)
- Changed `Phalcon\Image\Adapter\Gd::__construct()` to throw `Phalcon\Image\Exceptions\ImageLoadFailed` when an existing file cannot be read (for example a corrupt or unreadable image), instead of falling through to `Phalcon\Image\Exceptions\UnsupportedImageType` with no context. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/image/)
- Changed `Phalcon\Logger\AbstractLogger::addMessage()` to route each adapter through a direct `add()`/`process()` conditional instead of a dynamic method-name dispatch, and normalized the `defaultFormatter` class-string escaping in `Phalcon\Logger\Adapter\AbstractAdapter` (no behavior change). [#17155](https://github.com/phalcon/cphalcon/issues/17155) [[doc]](https://docs.phalcon.io/5.15/logger/)
- Changed `Phalcon\Logger\AbstractLogger` to accept an optional `Phalcon\Time\Clock\ClockInterface` constructor parameter; log item timestamps now come from the clock, defaulting to a `Phalcon\Time\Clock\SystemClock` on the logger timezone (current behavior preserved). [#17151](https://github.com/phalcon/cphalcon/issues/17151) [[doc]](https://docs.phalcon.io/5.15/logger/) [[doc]](https://docs.phalcon.io/5.15/time-clock/)
- Changed `Phalcon\Logger\Adapter\AbstractAdapter::__destruct()` to auto-commit an open transaction, flushing the queued items, instead of throwing `Phalcon\Logger\Exceptions\TransactionAlreadyActive`. Throwing from a destructor is fatal during script shutdown, so an exception unwinding past a `commit()` no longer escalates a lost log buffer into a process fatal. [#17155](https://github.com/phalcon/cphalcon/issues/17155) [[doc]](https://docs.phalcon.io/5.15/logger/)
- Changed `Phalcon\Logger\Adapter\AbstractAdapter::begin()` to throw `Phalcon\Logger\Exceptions\TransactionAlreadyActive` when a transaction is already active, instead of silently re-arming the flag. [#17155](https://github.com/phalcon/cphalcon/issues/17155) [[doc]](https://docs.phalcon.io/5.15/logger/)
- Changed `Phalcon\Logger\LoggerInterface`, `Phalcon\Logger\Adapter\AdapterInterface`, and `Phalcon\Logger\Formatter\FormatterInterface` to extend the new `Phalcon\Contracts\Logger\Logger`, `Phalcon\Contracts\Logger\Adapter\Adapter`, and `Phalcon\Contracts\Logger\Formatter\Formatter` contracts. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/logger/)
- Changed `Phalcon\Messages\Messages::appendMessages()` to reject a non-`Traversable` object with `Phalcon\Messages\Exceptions\MessagesNotIterable`, instead of a fatal error on `rewind()`. [#17154](https://github.com/phalcon/cphalcon/issues/17154) [[doc]](https://docs.phalcon.io/5.15/filter-validation/)
- Changed `Phalcon\Messages\Messages::offsetSet()` to require a `Phalcon\Messages\MessageInterface` instance, throwing `Phalcon\Messages\Exceptions\MessageNotObject` for any other value instead of accepting any object; the exception message now states the required type. [#17154](https://github.com/phalcon/cphalcon/issues/17154) [[doc]](https://docs.phalcon.io/5.15/filter-validation/)
- Changed `Phalcon\Messages\Messages` to implement the new `Phalcon\Contracts\Messages\Messages` contract; the concrete type is unchanged. [#17154](https://github.com/phalcon/cphalcon/issues/17154) [[doc]](https://docs.phalcon.io/5.15/filter-validation/)
- Changed `Phalcon\Mvc\Application\Exceptions\InvalidModuleDefinition` and `Phalcon\Cli\Console\Exceptions\InvalidModuleDefinition` to accept an optional module name and reason in the constructor. The exception message now identifies the offending module and why its definition is invalid. [#17146](https://github.com/phalcon/cphalcon/issues/17146) [[doc]](https://docs.phalcon.io/5.15/application/) [[doc]](https://docs.phalcon.io/5.15/application-cli/)
- Changed `Phalcon\Mvc\ModelInterface::find()` to document the four query dialects and their intended lanes (find-parameter arrays, `Phalcon\Mvc\Model\Query\Builder`, `Phalcon\Mvc\Model\Criteria`, and raw PHQL) (no behavior change). [#17166](https://github.com/phalcon/cphalcon/issues/17166) [[doc]](https://docs.phalcon.io/5.15/db-models/)
- Changed `Phalcon\Mvc\Model\MetaData` to document its two positional metadata array layouts on the class docblock - the 14-slot attribute array and the 2-slot column map - noting that the two constant families share numeric values and that the metadata cache adapters persist the layout (no behavior change). [#17166](https://github.com/phalcon/cphalcon/issues/17166) [[doc]](https://docs.phalcon.io/5.15/db-models-metadata/)
- Changed `Phalcon\Mvc\Model` to document the `__get`/`__call`/`__callStatic` magic-resolution order and finder grammar on its class docblock, and that `Phalcon\Mvc\Model::setup()` writes process-global `orm.*` settings that affect every model in the process (no behavior change). [#17166](https://github.com/phalcon/cphalcon/issues/17166) [[doc]](https://docs.phalcon.io/5.15/db-models/)
- Changed `Phalcon\Paginator\Adapter\AbstractAdapter` to require the `limit` option and `Phalcon\Paginator\Adapter\Model` to require the `model` option in their constructors, both throwing `Phalcon\Paginator\Exceptions\MissingRequiredParameter`, matching the `Phalcon\Paginator\Adapter\QueryBuilder` and `Phalcon\Paginator\Adapter\QueryBuilderCursor` adapters. Previously a missing `limit` reached a division-by-zero in `Model`/`NativeArray` and a missing `model` produced a notice followed by a fatal. [#17153](https://github.com/phalcon/cphalcon/issues/17153) [[doc]](https://docs.phalcon.io/5.15/db-pagination/)
- Changed `Phalcon\Paginator\Adapter\QueryBuilderCursor` to throw `Phalcon\Paginator\Exceptions\InvalidCursorColumn` when the cursor column value of the last row is non-numeric, instead of casting it to `0` and silently terminating pagination (e.g. over UUID or ULID keys). [#17153](https://github.com/phalcon/cphalcon/issues/17153) [[doc]](https://docs.phalcon.io/5.15/db-pagination/)
- Changed `Phalcon\Paginator\Adapter\QueryBuilder` to explicitly initialize its internal `hasMultipleGroups` flag and documented that the `columns` constructor option is consumed only by the total-count rewrite for `HAVING`/`GROUP BY` queries. [#17153](https://github.com/phalcon/cphalcon/issues/17153) [[doc]](https://docs.phalcon.io/5.15/db-pagination/)
- Changed `Phalcon\Session\Adapter\Redis::read()` to short-circuit a re-entrant read when this instance already holds the lock for the same session id, returning the held lock instead of spinning against its own lock for the full retry budget before throwing `Phalcon\Session\Adapter\Exceptions\AdapterRuntimeError` (the `session_reset()` re-read path). Only the self-deadlock scenario changes. [#17167](https://github.com/phalcon/cphalcon/issues/17167) [[doc]](https://docs.phalcon.io/5.15/session/)
- Changed `Phalcon\Session\Manager::setId()` to validate the supplied id against the PHP session-id alphabet (`[a-zA-Z0-9,-]`), throwing the new `Phalcon\Session\Exceptions\InvalidSessionId`, matching the cookie validation already performed in `start()` instead of passing an invalid id straight to `session_id()`. [#17167](https://github.com/phalcon/cphalcon/issues/17167) [[doc]](https://docs.phalcon.io/5.15/session/)
- Changed `Phalcon\Session\Manager::setName()` to reject a digits-only name with `Phalcon\Session\Exceptions\InvalidSessionName`, instead of letting PHP emit a `session_name()` warning and silently leave the name unchanged. [#17167](https://github.com/phalcon/cphalcon/issues/17167) [[doc]](https://docs.phalcon.io/5.15/session/)
- Changed `Phalcon\Storage\Adapter\AbstractAdapter::doGet()` and the `Phalcon\Storage\Adapter\Stream` counter and delete internals to route through the protected `do*` primitives instead of re-entering the public `get()`/`set()`/`has()`/`delete()` surface. A networked read on `Redis`/`Libmemcached` no longer issues a separate `EXISTS` before `GET` (roughly halving the round trips), and no fabricated nested `before*`/`after*` events are fired - event listeners now observe the real operation sequence (a behavior note for listeners, not a break). [#17168](https://github.com/phalcon/cphalcon/issues/17168) [[doc]](https://docs.phalcon.io/5.15/storage/)
- Changed `Phalcon\Storage\Adapter\AbstractAdapter::getArrVal()` to delegate to the canonical `Phalcon\Support\Helper\Arr\Get` helper instead of carrying its own diverged copy (no behavior change). [#17168](https://github.com/phalcon/cphalcon/issues/17168) [[doc]](https://docs.phalcon.io/5.15/storage/)
- Changed `Phalcon\Storage\Adapter\AbstractAdapter::getUnserializedData()` to guard the `isSuccess()` call with `method_exists()`, defaulting to success. A custom serializer implementing only `Phalcon\Storage\Serializer\SerializerInterface` (which does not declare `isSuccess()`) no longer fatals on the first cache miss or corrupt entry. [#17168](https://github.com/phalcon/cphalcon/issues/17168) [[doc]](https://docs.phalcon.io/5.15/storage/)
- Changed `Phalcon\Storage\Adapter\Redis::getKeys()` to enumerate keys with a non-blocking `SCAN` iteration instead of the blocking `KEYS *` command, removing the production hazard on large keyspaces while returning the same keys. `Phalcon\Storage\Adapter\RedisCluster::getKeys()` retains `KEYS` because cluster `SCAN` iterates one node at a time. [#17168](https://github.com/phalcon/cphalcon/issues/17168) [[doc]](https://docs.phalcon.io/5.15/storage/)
- Changed `Phalcon\Support\Collection::sort()` to throw `InvalidArgumentException` when given a non-callable callback, instead of fataling inside `uasort()`, and to compare against the `SORT_ASC`/`SORT_DESC` constants rather than the literals `4`/`3`. [#17152](https://github.com/phalcon/cphalcon/issues/17152) [[doc]](https://docs.phalcon.io/5.15/support-collection/)
- Changed `Phalcon\Support\Helper\Str\AbstractStr` and `Phalcon\Support\Helper\Arr\AbstractArr` to be marked `@internal`, documenting that they exist only for their respective helper hierarchies; new code should compose the invokable helpers (`Phalcon\Support\Helper\Str\Interpolate`, `Phalcon\Support\Helper\Arr\Get`) rather than extending these bases. [#17152](https://github.com/phalcon/cphalcon/issues/17152) [[doc]](https://docs.phalcon.io/5.15/support-helper/)
- Changed `Phalcon\Support\Settings::get()` and `Phalcon\Support\Settings::set()` to share a single internal reader for the setting whitelist instead of duplicating the list across the read and write paths, removing the drift risk between them. [#17152](https://github.com/phalcon/cphalcon/issues/17152) [[doc]](https://docs.phalcon.io/5.15/support-settings/)
- Changed `Phalcon\Translate\Adapter\AbstractAdapter` and `Phalcon\Translate\Interpolator\AssociativeArray` to memoize the interpolator instance and the `Phalcon\Support\Helper\Str\Interpolate` helper respectively, removing two object allocations per translation call on the hot path. [#17150](https://github.com/phalcon/cphalcon/issues/17150) [[doc]](https://docs.phalcon.io/5.15/translate/)
- Changed `Phalcon\Translate\Adapter\AbstractAdapter` to host the `triggerError` strict-mode option and the `notFound()` hook (lifted from `Phalcon\Translate\Adapter\NativeArray`), so `Phalcon\Translate\Adapter\Csv` and `Phalcon\Translate\Adapter\Gettext` now also support strict mode - a missing key throws `Phalcon\Translate\Exceptions\KeyNotFound` when `triggerError` is enabled. Defaults preserve current behavior. [#17150](https://github.com/phalcon/cphalcon/issues/17150) [[doc]](https://docs.phalcon.io/5.15/translate/)
- Changed several `Phalcon\Assets` exceptions to provide better descriptions. [#17147](https://github.com/phalcon/cphalcon/issues/17147) [[doc]](https://docs.phalcon.io/5.15/assets/)
- Changed the Redis fast path in `Phalcon\Cache\AbstractCache::doGetMultiple()` to validate each key, accept `Traversable` inputs (previously a `TypeError` on the Redis adapter only), and apply the serializer's full unserialize protocol (returning the default value for a corrupt entry), aligning it with the per-key loop path used by the other adapters. [#17164](https://github.com/phalcon/cphalcon/issues/17164) [[doc]](https://docs.phalcon.io/5.15/cache/)
- Changed the `Phalcon\Application\Exceptions\ModuleNotRegistered` message to be more descriptive. [#17146](https://github.com/phalcon/cphalcon/issues/17146) [[doc]](https://docs.phalcon.io/5.15/application/) [[doc]](https://docs.phalcon.io/5.15/application-cli/)
- Changed the `Phalcon\Auth\Guard\Session` `auth:*` events to fire as non-cancellable, matching that their return value was already ignored. [#17148](https://github.com/phalcon/cphalcon/issues/17148) [[doc]](https://docs.phalcon.io/5.15/auth/)
- Changed the `Phalcon\Auth\Guard\Session` remember-me cookie lifetime to a configurable `Phalcon\Auth\Guard\Config\SessionGuardConfig` value (`rememberTtl`, default `DEFAULT_REMEMBER_TTL`); the default is now 365 days (previously a hardcoded 360). [#17148](https://github.com/phalcon/cphalcon/issues/17148) [[doc]](https://docs.phalcon.io/5.15/auth/)
- Changed the `Phalcon\Auth` adapters (`Memory`, `Stream`, `Model`) to verify the configured user model implements `Phalcon\Contracts\Auth\AuthUser` during hydration, throwing `Phalcon\Auth\Exceptions\DoesNotImplement` instead of failing later. [#17148](https://github.com/phalcon/cphalcon/issues/17148) [[doc]](https://docs.phalcon.io/5.15/auth/)
- Changed the `Phalcon\Autoload\Loader` class-map strategy to fire the `loader:beforeCheckPath` event before probing, matching the namespace and directory strategies. [#17149](https://github.com/phalcon/cphalcon/issues/17149) [[doc]](https://docs.phalcon.io/5.15/autoload/)
- Changed the `Phalcon\Flash\Exceptions\EscaperServiceUnavailable` and `Phalcon\Flash\Exceptions\SessionServiceUnavailable` messages to "The '<service>' service is not available (no container, or service not registered)", describing both trigger conditions (no container at all, or a container that lacks the service). [#17158](https://github.com/phalcon/cphalcon/issues/17158) [[doc]](https://docs.phalcon.io/5.15/flash/)
- Deprecated `Phalcon\Assets\Filters\Cssmin` and `Phalcon\Assets\Filters\Jsmin`; both return the content unchanged (the minification has never been implemented). [#17147](https://github.com/phalcon/cphalcon/issues/17147) [[doc]](https://docs.phalcon.io/5.15/assets/)
- Deprecated `Phalcon\Cli\Router::getParams()` and `Phalcon\Cli\RouterInterface::getParams()` in favor of `getParameters()`. [#17165](https://github.com/phalcon/cphalcon/issues/17165) [[doc]](https://docs.phalcon.io/5.15/application-cli/)
- Deprecated the `Phalcon\Dispatcher\AbstractDispatcher` and `Phalcon\Dispatcher\DispatcherInterface` parameter accessors `getParam()`, `getParams()`, `hasParam()`, `setParam()`, and `setParams()` in favor of their `getParameter()`, `getParameters()`, `hasParameter()`, `setParameter()`, and `setParameters()` counterparts; the deprecated spellings will be removed in the next major version. [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Deprecated the `Phalcon\Encryption\Security` constants `CRYPT_STD_DES`, `CRYPT_EXT_DES`, `CRYPT_BLOWFISH`, and `CRYPT_BLOWFISH_Y` (never implemented; they resolve to bcrypt) and `CRYPT_MD5` (weak legacy algorithm); any unhandled `defaultHash` value resolves to bcrypt. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-security/)

### Added

- Added `Phalcon\Contracts\Assets\Asset` and `Phalcon\Contracts\Assets\Filter`, the canonical contracts for `Phalcon\Assets\Asset` and the Assets filters. They are the long-term replacements for the deprecated `Phalcon\Assets\AssetInterface` and `Phalcon\Assets\FilterInterface`. [#17147](https://github.com/phalcon/cphalcon/issues/17147) [[doc]](https://docs.phalcon.io/5.15/assets/)
- Added `Phalcon\Contracts\Cache\Cache`, the canonical contract for `Phalcon\Cache\Cache` (the PSR-16-shaped cache surface). It is the long-term replacement for the deprecated `Phalcon\Cache\CacheInterface`. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/cache/)
- Added `Phalcon\Contracts\Db\Geometry\Geometry`, the canonical contract for the `Phalcon\Db\Geometry` value objects (`getSrid()`, `getType()`, `toWkt()`). It is the long-term replacement for the deprecated `Phalcon\Db\Geometry\GeometryInterface`. [#17163](https://github.com/phalcon/cphalcon/issues/17163) [[doc]](https://docs.phalcon.io/5.15/db-layer/)
- Added `Phalcon\Contracts\Dispatcher\Dispatcher`, `Phalcon\Contracts\Mvc\Dispatcher`, and `Phalcon\Contracts\Cli\Dispatcher`, the canonical contracts for `Phalcon\Dispatcher\AbstractDispatcher`, `Phalcon\Mvc\Dispatcher`, and `Phalcon\Cli\Dispatcher`. They are the long-term replacements for the deprecated `Phalcon\Dispatcher\DispatcherInterface`, `Phalcon\Mvc\DispatcherInterface`, and `Phalcon\Cli\DispatcherInterface`. [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Added `Phalcon\Contracts\Flash\Flash`, the canonical contract for the `Phalcon\Flash` messengers (`Phalcon\Flash\Direct`, `Phalcon\Flash\Session`). It is the long-term replacement for the deprecated `Phalcon\Flash\FlashInterface`. [#17158](https://github.com/phalcon/cphalcon/issues/17158) [[doc]](https://docs.phalcon.io/5.15/flash/)
- Added `Phalcon\Contracts\Logger\Logger`, `Phalcon\Contracts\Logger\Adapter\Adapter`, and `Phalcon\Contracts\Logger\Formatter\Formatter`, the canonical contracts for `Phalcon\Logger\Logger`, its adapters, and its formatters. They are the long-term replacements for the deprecated `Phalcon\Logger\LoggerInterface`, `Phalcon\Logger\Adapter\AdapterInterface`, and `Phalcon\Logger\Formatter\FormatterInterface`. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/logger/)
- Added `Phalcon\Contracts\Messages\Messages`, the canonical contract for the `Phalcon\Messages\Messages` collection (append, count, filter, and iteration); the collection previously had no interface of its own. [#17154](https://github.com/phalcon/cphalcon/issues/17154) [[doc]](https://docs.phalcon.io/5.15/filter-validation/)
- Added `Phalcon\Db\Exceptions\InvalidDialectClass`, thrown by `Phalcon\Db\Adapter\AbstractAdapter::__construct()` when the `dialectClass` descriptor object does not implement `Phalcon\Db\DialectInterface`. [#17163](https://github.com/phalcon/cphalcon/issues/17163) [[doc]](https://docs.phalcon.io/5.15/db-layer/)
- Added `Phalcon\Dispatcher\AbstractDispatcher::getPreviousActionName()`, `Phalcon\Dispatcher\AbstractDispatcher::getPreviousHandlerName()`, and `Phalcon\Dispatcher\AbstractDispatcher::getPreviousNamespaceName()`, lifting the previously MVC-only previous-dispatch getters to the base so `Phalcon\Cli\Dispatcher` gains them too. `Phalcon\Mvc\Dispatcher::getPreviousControllerName()` remains as a controller-named alias. [#17159](https://github.com/phalcon/cphalcon/issues/17159) [[doc]](https://docs.phalcon.io/5.15/dispatcher/)
- Added `Phalcon\Encryption\Crypt\Exception\InvalidAuthTagLength` and `Phalcon\Encryption\Crypt\Exception\InvalidDecryptLength`. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Added `Phalcon\Image\Adapter\Gd::create()` and `Phalcon\Image\Adapter\Imagick::create()`, static named constructors for the blank-canvas case that avoid the constructor's load-or-create ambiguity. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/image/)
- Added `Phalcon\Image\Exceptions\InvalidColor`, thrown when a malformed hex color is passed to `Phalcon\Image\Adapter\AbstractAdapter::background()` or `Phalcon\Image\Adapter\AbstractAdapter::text()`. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/image/)
- Added `Phalcon\Logger\AbstractLogger::begin()`, `Phalcon\Logger\AbstractLogger::commit()`, and `Phalcon\Logger\AbstractLogger::rollback()`, which fan the transaction out across every non-excluded adapter, so transactions can be controlled at the logger level instead of one adapter at a time. [#17155](https://github.com/phalcon/cphalcon/issues/17155) [[doc]](https://docs.phalcon.io/5.15/logger/)
- Added a correctly-spelled `"pkcs7"` service alias to `Phalcon\Encryption\Crypt\PadFactory` alongside the existing (misspelled) `"pjcs7"`, so `newInstance("pkcs7")` resolves the PKCS7 padding. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Added an optional `sessionKey` constructor parameter to `Phalcon\Flash\Session` (defaulting to the existing `_flashMessages` key), letting multi-instance applications namespace their flash messages instead of sharing one session slot. [#17158](https://github.com/phalcon/cphalcon/issues/17158) [[doc]](https://docs.phalcon.io/5.15/flash/)
- Added the `Phalcon\Db\Dialect::supportsReturning()`, `Phalcon\Db\Dialect::supportsOnConflictUpdate()`, `Phalcon\Db\Dialect::supportsMaterializedViews()`, and `Phalcon\Db\Dialect::supportsAlterTable()` capability predicates (overridden per dialect to match each backend) so callers can detect an optional feature instead of catching its dedicated exception. [#17163](https://github.com/phalcon/cphalcon/issues/17163) [[doc]](https://docs.phalcon.io/5.15/db-layer/)

### Fixed

- Fixed `Phalcon\Autoload\Loader` reporting a successful autoload for a class-map entry whose mapped file does not exist; the class-map strategy now honors the `require` result and falls through to the namespace and directory strategies instead of short-circuiting them. [#17149](https://github.com/phalcon/cphalcon/issues/17149) [[doc]](https://docs.phalcon.io/5.15/autoload/)
- Fixed `Phalcon\Db\Adapter\Pdo\Mysql::describeColumns()` treating a column declared `DEFAULT NULL` as having the string `"NULL"` as its default (MySQL's `INFORMATION_SCHEMA.COLUMNS.COLUMN_DEFAULT` reports it that way since the 5.13.0 switch away from `SHOW FULL COLUMNS`); the sentinel is now read as no default. This stops a nullable column whose value is `null` from being turned into the string `"NULL"` on the model attribute after `save()`. [#17176](https://github.com/phalcon/cphalcon/issues/17176) [[doc]](https://docs.phalcon.io/5.15/db-layer/)
- Fixed `Phalcon\Encryption\Crypt::decrypt()` extracting the GCM/CCM authentication tag with `str_replace()`, which stripped every occurrence of the tag's byte sequence from the ciphertext rather than only the trailing tag. With short auth tags an interior collision corrupted the ciphertext and made a valid AEAD payload fail authentication; the tag is now split off by length. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Fixed `Phalcon\Encryption\Crypt\Padding\Pkcs7` and `Phalcon\Encryption\Crypt\Padding\Zero` measuring binary padded data with the encoding-sensitive `mb_strlen()`; on a multibyte-capable internal encoding this computed the padding size from the wrong byte and silently mis-unpadded. Both now use byte-true `strlen()`. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-crypt/)
- Fixed `Phalcon\Encryption\Security::hash()` discarding caller-supplied Argon2 tuning options (`memory_cost`, `time_cost`, `threads`); it rebuilt the options array with only `cost`, so a hardened configuration silently ran with defaults. The caller's options are now preserved. [#17160](https://github.com/phalcon/cphalcon/issues/17160) [[doc]](https://docs.phalcon.io/5.15/encryption-security/)
- Fixed `Phalcon\Filter\Validation::bind()` emitting an `Invalid arguments supplied for camelize()` warning when the data being validated against an entity contained a numeric (integer) key, as happened with `Phalcon\Forms\Form::isValid($data, $entity)`; integer keys are now skipped during entity binding. [#17173](https://github.com/phalcon/cphalcon/issues/17173) [[doc]](https://docs.phalcon.io/5.15/filter-validation/)
- Fixed `Phalcon\Logger\AbstractLogger::excludeAdapters()` leaving the exclusion list armed across calls when the level filter discarded the message; the list is now cleared on every `Phalcon\Logger\AbstractLogger::addMessage()` call, so an exclusion is consumed by the call that set it instead of skipping adapters on the next, unrelated log call. [#17155](https://github.com/phalcon/cphalcon/issues/17155) [[doc]](https://docs.phalcon.io/5.15/logger/)
- Fixed `Phalcon\Mvc\Model::sum()` and `Phalcon\Mvc\Model::average()` returning `null` on an empty result set, which violated their declared `float | ResultsetInterface` return type and raised a `TypeError` in any subclass that overrode them with the same type and delegated through `parent`. Both now coerce the empty aggregate to `0.0`. [#17184](https://github.com/phalcon/cphalcon/issues/17184) [[doc]](https://docs.phalcon.io/5.15/db-models/)
- Fixed `Phalcon\Mvc\View\Engine\Volt\Compiler::resolveFilter()` building the `join` filter by splicing the raw separator and array argument token values into the generated PHP - the separator dropped between two literal single quotes and the array emitted unquoted - instead of compiling them through `expression()` as every other Volt literal is. A `join` separator literal containing a single quote closed the generated `join('...')` call and injected arbitrary statements into the compiled template, which the Volt engine then ran when the view was rendered. Both arguments are now emitted through `expression()`, which quotes and escapes the separator. [[doc]](https://docs.phalcon.io/5.15/volt/)
- Fixed `Phalcon\Support\Collection` (and every descendant, including `Phalcon\Config\Config` and `Phalcon\Html\Attributes`) orphaning the previously stored entry when a key was overwritten with a different letter casing on a case-insensitive collection. `set("Key", 1)` followed by `set("KEY", 2)` left both entries in the backing store, so `count()`, `toArray()`, and iteration disagreed with `get()`, `has()`, and `remove()`. The stale entry is now evicted before the new value is written. [#17152](https://github.com/phalcon/cphalcon/issues/17152) [[doc]](https://docs.phalcon.io/5.15/support-collection/)
- Fixed `Phalcon\Time\Clock\FrozenClock::adjust()` leaving the process-global `warning.enable` flag clobbered on the pre-PHP-8.3 fallback path; the prior value is now restored on both exits. [#17151](https://github.com/phalcon/cphalcon/issues/17151) [[doc]](https://docs.phalcon.io/5.15/time-clock/)
- Fixed the `Phalcon\Acl\Adapter\Memory` documentation and metadata. [#17143](https://github.com/phalcon/cphalcon/issues/17143) [[doc]](https://docs.phalcon.io/5.15/acl/)
- Fixed the `Phalcon\Autoload\Loader` debug trail (`getDebug()`) being wiped by nested autoloads (a class requiring a not-yet-loaded parent); the trail is now reset only on the outermost call. [#17149](https://github.com/phalcon/cphalcon/issues/17149) [[doc]](https://docs.phalcon.io/5.15/autoload/)
- Fixed the `loader:pathFound` event in `Phalcon\Autoload\Loader` firing more than once per load (and, for the class-map strategy, before the file was confirmed); it now fires once, from the point that loads the file. [#17149](https://github.com/phalcon/cphalcon/issues/17149) [[doc]](https://docs.phalcon.io/5.15/autoload/)

### Removed

- Removed `Phalcon\Cache\Exception\InvalidCacheKey` and `Phalcon\Cache\Exception\CacheKeysNotIterable`. `Phalcon\Cache\AbstractCache::checkKey()` and `checkKeys()` now throw `Phalcon\Cache\Exception\InvalidArgumentException` with the same messages. [#17156](https://github.com/phalcon/cphalcon/issues/17156) [[doc]](https://docs.phalcon.io/5.15/cache/)

## [5.14.2](https://github.com/phalcon/cphalcon/releases/tag/v5.14.2) (2026-06-12)

### Tools

- Zephir Parser v2.0.4
- Zephir 0.23.0 (development - 27535f802)

### Changed

- Changed `Phalcon\Cli\Console::handle()` to process module definitions the same way as `Phalcon\Mvc\Application::handle()`. The module is now resolved through the inherited `getModule()`, so an unregistered module throws `Phalcon\Application\Exceptions\ModuleNotRegistered` (as `Console::getModule()` already did) instead of `Phalcon\Cli\Console\Exceptions\ConsoleModuleNotRegistered`. `Closure` module definitions are now supported and are invoked with the container, matching MVC. A definition that is neither an array nor a `Closure` throws the new `Phalcon\Cli\Console\Exceptions\InvalidModuleDefinition` instead of `InvalidModuleDefinitionPath`. [#17107](https://github.com/phalcon/cphalcon/issues/17107)
- Changed `Phalcon\Config\Adapter\Ini::parseIniString()` to use `Phalcon\Config\Config::DEFAULT_PATH_DELIMITER` for the key nesting separator instead of a hardcoded `.` (no behavior change). [#17134](https://github.com/phalcon/cphalcon/issues/17134)
- Changed `Phalcon\Config\Adapter\Json` and `Phalcon\Config\Adapter\Php` to throw `Phalcon\Config\Exceptions\CannotLoadConfigFile` when the configuration file cannot be read, instead of failing inside the JSON decoder (`Json`) or with a fatal `require` error (`Php`). All file based Config adapters now share the same failure contract. [#17134](https://github.com/phalcon/cphalcon/issues/17134)
- Changed `Phalcon\Config\ConfigFactory` to resolve adapter-specific constructor arguments (`mode` for `ini`, `callbacks` for `yaml`) through a single internal parameter map consulted by both `load()` and `newInstance()`, instead of two hardcoded switches. `load()` now also resolves the `yml` adapter name / file extension to the `yaml` adapter. [#17134](https://github.com/phalcon/cphalcon/issues/17134)
- Consolidated the `allowEmpty` handling of `Phalcon\Filter\Validation` into the validator (`Phalcon\Filter\Validation\AbstractValidator::isAllowEmpty()`). The per-field `allowEmpty` map is also honored. [#17124](https://github.com/phalcon/cphalcon/issues/17124)
- Moved the resolution of an array `attribute` option from `Phalcon\Filter\Validation\AbstractValidator::getOption()` into `Phalcon\Filter\Validation\Validator\Uniqueness::getOption()`. [#17124](https://github.com/phalcon/cphalcon/issues/17124)

### Added

- Added `Phalcon\Filter\Filter::getDefaultMapper()`, for mapper services used also by `Phalcon\Filter\FilterFactory::getServices()`. [#17124](https://github.com/phalcon/cphalcon/issues/17124)
- Added `Phalcon\Filter\Validation\Validator\File\Resolution\AspectRatio`, validating that an uploaded image has an exact aspect ratio. The `ratio` option uses the `16x9` format (per-field array form supported) and is compared with integer cross-multiplication, so the dimensions must match exactly: 1920x1080 matches `16x9`, 1366x768 does not. Also available through the composite `Phalcon\Filter\Validation\Validator\File` via the `aspectRatio` and `messageAspectRatio` options. [#17104](https://github.com/phalcon/cphalcon/issues/17104)
- Added `SessionUpdateTimestampHandlerInterface` support to the `Phalcon\Session` adapters (`Noop`, `Stream`, `Redis`, `Libmemcached`), enabling PHP's `session.lazy_write` (on by default): when the session data is unchanged at close, PHP now calls `updateTimestamp()` instead of `write()`. `Stream` touches the session file without rewriting its data; `Redis` and `Libmemcached` delegate to `write()` to refresh the TTL. With `session.use_strict_mode` enabled, the new `validateId()` rejects uninitialized session ids. [#17129](https://github.com/phalcon/cphalcon/issues/17129)
- Added a `stripPrefix` option (default `true`) to the `Phalcon\Storage` / `Phalcon\Cache` adapters, controlling whether a leading prefix is stripped from incoming keys (the behavior introduced for [#17089](https://github.com/phalcon/cphalcon/issues/17089)). `Phalcon\Session\Adapter\Redis` and `Phalcon\Session\Adapter\Libmemcached` disable it by default: session ids are externally generated, so an id that happens to start with the prefix text must not collide with another session. [#17127](https://github.com/phalcon/cphalcon/issues/17127)
- Added an optional `Phalcon\Config\ConfigFactory` constructor parameter to `Phalcon\Config\Adapter\Grouped`. The factory is created once and reused for every configuration fragment, so custom adapters registered on a supplied factory are now visible when loading grouped configurations. [#17134](https://github.com/phalcon/cphalcon/issues/17134)
- Added dialect-specific operators to PHQL: `@@`, `@>`, `<@`, `&&`, `||`, `->`, `->>`, `#>`, `#>>`. Each is parsed into a binary expression and emitted only by the dialects that support it (PostgreSQL: all nine; MySQL: `->`, `->>`; SQLite: `||`, `->`, `->>`); using an operator on a dialect that does not support it throws `Phalcon\Db\Exceptions\UnsupportedOperator`. The jsonb existence operators (`?`, `?|`, `?&`, `@?`) and the `~` regex family are intentionally unsupported - use their function equivalents (e.g. `jsonb_exists()`, `regexp_like()`). [#14954](https://github.com/phalcon/cphalcon/issues/14954) [#14579](https://github.com/phalcon/cphalcon/issues/14579)
- Added geometry value objects under `Phalcon\Db\Geometry` (`Point`, `LineString`, `Polygon`, `MultiPoint`, `MultiLineString`, `MultiPolygon`, `GeometryCollection`) and read-side hydration of spatial columns. When `orm.cast_on_hydrate` is enabled, spatial column values (MySQL WKB / PostGIS EWKB) are decoded into these objects on model read; otherwise the raw value is returned unchanged. [#17110](https://github.com/phalcon/cphalcon/issues/17110) [#14769](https://github.com/phalcon/cphalcon/issues/14769) [#13670](https://github.com/phalcon/cphalcon/issues/13670)
- Added opt-in session locking to `Phalcon\Session\Adapter\Redis`, preventing concurrent requests from racing on the same session (stale reads / lost writes). When enabled with the new `lockingEnabled` constructor option, `read()` acquires a per-session lock (`SET NX EX`, retried with pauses) and `close()` / `destroy()` release it with a token-guarded delete, so an adapter can only remove a lock it still owns. A read that cannot acquire the lock throws `Phalcon\Session\Adapter\Exceptions\AdapterRuntimeError`. Tunable via the `lockExpiry` (lock TTL in seconds, default `30`), `lockRetries` (maximum attempts, default `100`) and `lockWaitTime` (microseconds between attempts, default `50000`) options. Locking is off by default. [#17126](https://github.com/phalcon/cphalcon/issues/17126)
- Added table comment support to the MySQL and PostgreSQL dialects. Comment values are single-quote escaped (the existing PostgreSQL column-comment emission is now escaped as well). SQLite has no native table comment and ignores the option. [#15258](https://github.com/phalcon/cphalcon/issues/15258)
- Added the `Phalcon\Contracts\Filter\Sanitizer` interface and moving array recursion in `Phalcon\Filter\Filter::sanitize()`. [#17124](https://github.com/phalcon/cphalcon/issues/17124)

### Fixed

- Fixed `Phalcon\Config\Config::cloneEmpty()` so that `filter()`, `map()`, `sort()` and `where()` no longer fail on adapter instances (`Ini`, `Json`, `Php`, `Yaml`, `Grouped`). The override clones the current instance and replaces its data instead of invoking the adapter constructor with the parent `(array $data, ...)` signature. [#17134](https://github.com/phalcon/cphalcon/issues/17134)
- Fixed `Phalcon\Config\Config::merge()` emptying the configuration before validating its argument. Invalid merge data still throws `Phalcon\Config\Exceptions\InvalidMergeData`, but the current configuration now survives intact. [#17134](https://github.com/phalcon/cphalcon/issues/17134)
- Fixed `Phalcon\Config\Config::setData()` bypassing the `Phalcon\Support\Collection` runtime type guard: a `type` passed to the constructor is now enforced on leaf values at every nesting depth (arrays become nested Config objects, which validate their own leaves). Nested Config objects also inherit the `strictNull` and `type` flags in addition to `insensitive`. [#17134](https://github.com/phalcon/cphalcon/issues/17134)
- Fixed `Phalcon\Http\Cookie::send()` fataling when the cookie has a non-empty definition and no DI container is set. The session integration (service lookup, started check, and the `_PHCOOKIE_` key convention) is now consolidated in private `getStartedSession()`/`getSessionKey()` helpers shared by `delete()`, `restore()` and `send()`. [#17127](https://github.com/phalcon/cphalcon/issues/17127)
- Fixed `Phalcon\Mvc\Model::groupResult()` declaring a `Phalcon\Mvc\Model\ResultsetInterface` return type while returning a scalar (`int`, `float`, `string`, or `null`) for non-grouped aggregate queries (`count()`, `sum()`, `average()`, `maximum()`, `minimum()`). The return type declaration has been removed (`@return int|float|string|null|ResultsetInterface`), so model subclasses can override `groupResult()` without risking a `TypeError`. [#17114](https://github.com/phalcon/cphalcon/issues/17114)
- Fixed `Phalcon\Mvc\Model::save()` and `Phalcon\Mvc\Model::update()` to run the record-existence check on the write connection instead of the read connection. On master-replica setups, replication lag could make the check miss a row already written to the master, causing `save()` to attempt an `INSERT` instead of an `UPDATE`, or `update()` to fail with `Record cannot be updated because it does not exist`. `create()` has used the write connection since [#14256](https://github.com/phalcon/cphalcon/issues/14256). [#17105](https://github.com/phalcon/cphalcon/issues/17105)
- Fixed `Phalcon\Session\Bag` calling `getDI()` on its `Phalcon\Session\ManagerInterface` constructor parameter - a method the interface does not declare - which fataled for any manager implementing only the interface. The container is now captured only when the manager provides one. [#17127](https://github.com/phalcon/cphalcon/issues/17127)
- Fixed `Phalcon\Session\Manager::start()` deleting valid session cookies when `session.sid_bits_per_character` is `6`: the cookie sanity check now accepts the full PHP session ID alphabet (`[a-zA-Z0-9,-]`) instead of alphanumerics only. [#17127](https://github.com/phalcon/cphalcon/issues/17127)
- Fixed the Volt parser raising `Syntax error, unexpected token DEFAULT` when the `default` filter (e.g. `{{ value|default('text') }}`) is used inside a `{% switch %}` block. The word `default` is now treated as the `{% default %}` clause only when it directly follows the opening `{%` delimiter inside a switch; everywhere else it is parsed as a plain identifier, so the filter, `{{ default }}` and `{% set default = ... %}` all work inside switch-case blocks. [#16003](https://github.com/phalcon/cphalcon/issues/16003)
- Fixed the `Phalcon\Storage` / `Phalcon\Cache` adapters to accept keys already carrying the adapter prefix, so keys returned by `getKeys()` can be passed straight back to `get()`, `has()`, `delete()`, `deleteMultiple()`, `set()`, `setForever()`, `increment()` and `decrement()`. `Phalcon\Storage\Adapter\AbstractAdapter` now strips a leading prefix from incoming keys. [#17089](https://github.com/phalcon/cphalcon/issues/17089)
- Fixed the alternative installation script (`build/install`) to set the installed `phalcon.so` to mode `0644` after `make install`. The PHP build system installs shared extensions with the `install` default mode `0755`; shared objects only need read permission. [#17113](https://github.com/phalcon/cphalcon/issues/17113)

### Removed

- Removed `Phalcon\Cli\Console\Exceptions\ConsoleModuleNotRegistered` and `Phalcon\Cli\Console\Exceptions\InvalidModuleDefinitionPath`, superseded by `Phalcon\Application\Exceptions\ModuleNotRegistered` and `Phalcon\Cli\Console\Exceptions\InvalidModuleDefinition`. [#17107](https://github.com/phalcon/cphalcon/issues/17107)

## [5.14.1](https://github.com/phalcon/cphalcon/releases/tag/v5.14.1) (2026-06-08)

### Tools

- Zephir Parser v2.0.4
- Zephir 0.23.0 (development - bae82f7bd)

### Changed

- Consolidated the `Phalcon\Auth` dual-container handling (new `Phalcon\Container\Container` vs legacy `Phalcon\Di\Di`) behind a single internal `Phalcon\Auth\Internal\ContainerResolver`. [#17088](https://github.com/phalcon/cphalcon/issues/17088) [[doc]](https://docs.phalcon.io/5.14/auth/)
- Renamed the private `Phalcon\Events\Manager` dispatch hot-loop helper to `runQueue()`. [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.14/events/)
- Reworked the `Phalcon\Auth` access gates into Specification-style policies. `Phalcon\Contracts\Auth\Access\Access::isAllowed()` now receives the current identity and the request context: `isAllowed(Guard $guard, string $actionName, array $context = [])`, where context carries `handler` (controller / task / Micro component name), `module` (MVC module, when present), and `params` (dispatcher or route parameters). [#17088](https://github.com/phalcon/cphalcon/issues/17088) [[doc]](https://docs.phalcon.io/5.14/auth/)
- `Phalcon\Auth\Manager::access()` now resolves gates through `Phalcon\Auth\Access\AccessLocator` from the container instead of constructing them directly. [#17088](https://github.com/phalcon/cphalcon/issues/17088) [[doc]](https://docs.phalcon.io/5.14/auth/)

### Added

- Added `Phalcon\Auth\Access\Acl` - an ACL-backed access gate that incorporates the role-based authorization of the old Firewall component ([#14630](https://github.com/phalcon/cphalcon/issues/14630)) into the Auth layer. The gate checks the authenticated user's role against a `Phalcon\Acl\Adapter\AdapterInterface`: the ACL component is the `handler` context key (prefixed with `module` and a configurable separator when present), the ACL access is the action name, and `params` are passed through to callable ACL rules. Unauthenticated requests resolve to a configurable guest role (default `guest`); authenticated users supply their role via `Phalcon\Acl\RoleAwareInterface`. [#17088](https://github.com/phalcon/cphalcon/issues/17088) [[doc]](https://docs.phalcon.io/5.14/auth/)
- Added `Phalcon\Auth\Micro\AuthMicroListener` to enforce the active Auth access gate on `Phalcon\Mvc\Micro` route execution (attach to the `micro` event space).[#17088](https://github.com/phalcon/cphalcon/issues/17088) [[doc]](https://docs.phalcon.io/5.14/auth/)
- Added `Phalcon\Events\Manager::dispatch(object $event, string|array|null $name = null, ?object $source = null)` for object/class-based event dispatch built on Phalcon's own `Phalcon\Contracts\Events\Stoppable`. Listeners are routed by an explicit name (a string, or a `[class, method]` array) or by the event's class name and receive the event object. [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.14/events/)
- Added `beforeBind` and `afterBind` hook methods to `Phalcon\Forms\Form`. When defined on a form subclass, `beforeBind(array $data, ?object $entity)` runs at the start of `bind()` (returning `false` cancels the bind) and `afterBind(?object $entity)` runs after the data has been assigned. Both also fire when `bind()` is reached through `isValid()`. [#14598](https://github.com/phalcon/cphalcon/issues/14598) [[doc]](https://docs.phalcon.io/5.14/forms/)
- Added a `sync` option to many-to-many (`hasManyToMany`) relations and a chainable `Phalcon\Mvc\Model::setSync()` method to synchronize related records on save. When enabled, saving deletes the intermediate rows for records no longer present in the assigned array (add/update/delete), instead of only appending. [#17071](https://github.com/phalcon/cphalcon/issues/17071) [[doc]](https://docs.phalcon.io/5.14/db-models-relationships/)
- Added a `trace()` method to `Phalcon\Logger\Logger` together with a new `TRACE` log level (value `9`, label `trace`). [#17047](https://github.com/phalcon/cphalcon/issues/17047) [[doc]](https://docs.phalcon.io/5.14/logger/)
- Added a `{% verbatim %}`/`{% endverbatim %}` tag to Volt. Its body is emitted exactly as written, without being parsed by Volt, so `{{ ... }}`, `{% ... %}`, `{# ... #}` and constructs such as `<?xml ... ?>` or client-side templates (Handlebars, Mustache, Angular) pass through untouched. [#17085](https://github.com/phalcon/cphalcon/issues/17085) [[doc]](https://docs.phalcon.io/5.14/volt/)
- Added support for `JOIN` clauses in PHQL `UPDATE` statements (e.g. `UPDATE Invoices INNER JOIN Customers ON ... SET ... WHERE Customers.cst_id = :id:`). The join is used to filter the records to update; the statement still targets a single model. [#16984](https://github.com/phalcon/cphalcon/issues/16984) [[doc]](https://docs.phalcon.io/5.14/db-phql/)

### Fixed

- Fixed PHQL parser cache to use string-keyed lookups (`zend_hash_str_find`/`zend_hash_str_update`) instead of integer keys derived from `zend_inline_hash_func`, eliminating hash collisions that caused different PHQL queries to return identical cached ASTs [#14791](https://github.com/phalcon/cphalcon/issues/14791)
- Fixed `Phalcon\Annotations\Reader` failing to parse a docblock when an annotation argument is a string literal containing a parenthesis (e.g. `@SomeAnnotation(key='value(')`). The docblock pre-scan that locates each `@Annotation(...)` span counted every `(`/`)`, including those inside quoted values, so an unbalanced parenthesis in a string consumed the rest of the comment and produced a "Scanning error". [#16084](https://github.com/phalcon/cphalcon/issues/16084)
- Fixed `Phalcon\Di\Injectable::__get()` to no longer cache resolved services as dynamic object properties. Services accessed via magic properties (e.g. `$this->request`) are now re-resolved through the container on each access, so replacing or updating a service in the container is reflected in controllers, views, and other injectable classes. Properties already declared on the class continue to be populated. [#17052](https://github.com/phalcon/cphalcon/issues/17052)
- Fixed `Phalcon\Encryption\Crypt::decrypt()` to verify the HMAC tag with the constant-time `hash_equals()` instead of the identity operator, removing an observable timing discrepancy in the tag comparison (CWE-208, CWE-347) . The tag is now also verified before the decrypted text is unpadded, and truncated tags are rejected by the unequal-length path of `hash_equals()`. [#17090](https://github.com/phalcon/cphalcon/issues/17090) [[doc]](https://docs.phalcon.io/5.14/encryption-crypt/)
- Fixed `Phalcon\Mvc\Model\Query\Builder::orderBy()` when the array syntax is used with complex PHQL expressions. Previously any array item containing a space was split as a simple `column direction` pair, corrupting expressions such as `CASE WHEN inv_status_flag = 1 THEN 0 ELSE 1 END ASC`. The builder now only treats a trailing `ASC`/`DESC` as the direction (autoescaping a simple column) and preserves complex expressions verbatim. [#17077](https://github.com/phalcon/cphalcon/issues/17077)
- Fixed `Phalcon\Mvc\Model\Query` (PHQL) parsing of identifiers whose name begins with the `NOT` keyword. Columns, tables, and aliases such as `notice_id` were truncated to `ice_id` (the leading `not` was dropped), causing the database to report the column as unknown - most visibly in `Phalcon\Mvc\Model\Query\Builder` join conditions built via `createBuilder()`. The scanner's re2c backtracking marker shared the token-start pointer, so the `NOT BETWEEN` rule advanced it past `not`; escaped identifiers containing internal escapes (e.g. `[col\[0\]]`) were corrupted by the same root cause. [#16831](https://github.com/phalcon/cphalcon/issues/16831) [#17087](https://github.com/phalcon/cphalcon/issues/17087)
- Fixed the compilation failure (`'name_zv' undeclared`) in `Phalcon\Container\Container::callableGet()` and `callableNew()`. Both closures captured the typed `string name` parameter directly via `use (name)`. [#17078](https://github.com/phalcon/cphalcon/issues/17078)
- Fixed the build from emitting a warning for `RedisCluster` not being present at compile time. [#2589](https://github.com/zephir-lang/zephir/pull/2589) [#16977](https://github.com/phalcon/cphalcon/issues/16977)

### Removed

- Removed the unfinished `{% raw %}`/`{% endraw %}` Volt tag. It never produced output (compilation threw `UnknownVoltStatement`) and its body was parsed rather than emitted literally. Use `{% verbatim %}` instead. [#17085](https://github.com/phalcon/cphalcon/issues/17085)

## [5.14.0](https://github.com/phalcon/cphalcon/releases/tag/v5.14.0) (2026-06-04)

### Tools

- Zephir Parser v2.0.3
- Zephir 0.22.0 (development - 9d2def774)

### Changed

- Alignment with v6; docblocks; sorting; return types; minor fixes (image watermark opacity calc, serializer/helpers, readonly-becoming-mutable, ACL local access). [#17055](https://github.com/phalcon/cphalcon/issues/17055)
- Changed return types to `-> <static>` or `-> <self>` in various components. The change is a covariant narrowing on implementation methods and does not touch any interface contracts, so userland classes that implement Phalcon interfaces and return the interface type continue to work unchanged. [#17035](https://github.com/phalcon/cphalcon/issues/17035)
- Internal performance work across `Autoload`, `Dispatcher`, `Annotations`, `Db`, `Mvc\Model`, `Mvc\Model\Query`, `Tag`, `Assets`, `Acl\Adapter\Memory`, `Http\Request`, `Encryption\Crypt`. Behavior preserved. [#17049](https://github.com/phalcon/cphalcon/issues/17049)
- Moved CI tools/scripts in `resources/` removed unused ones [#17066](https://github.com/phalcon/cphalcon/pull/17066)
- Moved docker in `resources/` [#17066](https://github.com/phalcon/cphalcon/pull/17066)
- Refactored docker images (more flexible less cruft) [#17066](https://github.com/phalcon/cphalcon/pull/17066)
- Reorganization of quality tool config files (in `resources/`) [#17066](https://github.com/phalcon/cphalcon/pull/17066)
- `Phalcon\Autoload\Loader` getters (`getDirectories`, `getExtensions`, `getFiles`) return arrays keyed by the value string instead of by a SHA256 hash of it; iteration order and contents are unchanged. [#17049](https://github.com/phalcon/cphalcon/issues/17049) [[doc]](https://docs.phalcon.io/5.14/autoload/)
- `Phalcon\Mvc\Router::handle()` internal optimizations: O(1) hash lookup for literal-URI routes; per-HTTP-method buckets; hot-loop reads; PCRE patterns chunked; per-route metadata cache deduplicated by route id. [#17012](https://github.com/phalcon/cphalcon/issues/17012) [[doc]](https://docs.phalcon.io/5.14/routing/)
- `Phalcon\Mvc\Router\Route::getCompiledHostName()` now uses cache for hostname/converters. [#17012](https://github.com/phalcon/cphalcon/issues/17012) [[doc]](https://docs.phalcon.io/5.14/routing/)

### Added

- Added a new dependency-injection container under `Phalcon\Container`, with its contracts under `Phalcon\Contracts\Container`. It adds:
    - `Phalcon\Container\Container` / `Phalcon\Container\ContainerFactory` - the container and its factory, configured through `Phalcon\Contracts\Container\Service\Provider` providers (`Phalcon\Container\Provider\Web`, `Phalcon\Container\Provider\Cli`).
    - `Phalcon\Container\Definition\ServiceDefinition` - fluent service definitions with autowiring, factories, extenders, tags, aliases, and configurable service lifetimes (`Phalcon\Container\Definition\ServiceLifetime`).
    - `Phalcon\Container\Resolver\Resolver` - reflection-based constructor / method / parameter autowiring, plus the `Phalcon\Container\Resolver\Lazy\*` family for lazy resolution (`Get`, `GetCall`, `NewInstance`, `Call`, `Env`, `CsEnv`, `ArrayValues`, etc.).
    - `Phalcon\Container\Exceptions\*` - granular, per-cause exceptions (`ServiceNotFound`, `CircularAliasFound`, `FrozenDefinition`, `CannotResolveParameter`, `NoProcessorFound`, etc.). [#16897](https://github.com/phalcon/cphalcon/issues/16897) [[doc]](https://docs.phalcon.io/5.14/container/)
- Added a new authentication and authorization layer under `Phalcon\Auth`, with its contracts under `Phalcon\Contracts\Auth`. Built on top of `Phalcon\Container`, it adds:
    - `Phalcon\Auth\Manager` / `Phalcon\Auth\ManagerFactory` - the central manager that wires guards and access gates together, and its factory.
    - `Phalcon\Auth\AuthUser` - a lightweight user value object returned by array-backed adapters when no application model class is configured.
    - Guards under `Phalcon\Auth\Guard` - `Session` and `Token` (with `AbstractGuard` and `UserRemember`), resolved via `Phalcon\Auth\Guard\GuardLocator` and configured through `Phalcon\Auth\Guard\Config\*` (`AbstractGuardConfig`, `SessionGuardConfig`, `TokenGuardConfig`).
    - Adapters under `Phalcon\Auth\Adapter` - `Memory`, `Model`, and `Stream` user providers (with `AbstractAdapter` and `AbstractArrayAdapter`), resolved via `Phalcon\Auth\Adapter\AdapterLocator` and configured through `Phalcon\Auth\Adapter\Config\*` (`AbstractAdapterConfig`, `MemoryAdapterConfig`, `ModelAdapterConfig`, `StreamAdapterConfig`).
    - Access gates under `Phalcon\Auth\Access` - `Auth` and `Guest` (with `AbstractAccess`), resolved via `Phalcon\Auth\Access\AccessLocator`.
    - Dispatcher listeners `Phalcon\Auth\Mvc\AuthDispatcherListener` and `Phalcon\Auth\Cli\AuthDispatcherListener` (with `Phalcon\Auth\AbstractAuthDispatcherListener`) to guard MVC and CLI dispatch.
    - `Phalcon\Auth\Exception` plus granular `Phalcon\Auth\Exceptions\*` (`AccessDenied`, `ConfigRequiresNonEmptyValue`, `DataMustContainIdKey`, `DoesNotImplement`, `FileCannotRead`, `FileDoesNotContainJson`, `FileDoesNotExist`, `FileNotValidJson`).
    - Contracts under `Phalcon\Contracts\Auth` - `Manager`, `AuthUser`, `AuthRemember`, `RememberToken`, `Access\Access`, `Adapter\Adapter`, `Adapter\AdapterConfig`, `Adapter\RememberAdapter`, `Guard\Guard`, `Guard\GuardConfig`, `Guard\GuardStateful`, `Guard\BasicAuth`.
    - `Phalcon\Support\AbstractLocator` - the shared service-locator base extended by the guard, adapter, and access locators. [#16273](https://github.com/phalcon/cphalcon/issues/16273) [[doc]](https://docs.phalcon.io/5.14/auth/)
- Added granular exception classes across the framework. Every namespace that previously surfaced failures through a single umbrella `Phalcon\<Namespace>\Exception` (or its sub-namespace counterpart) now ships per-cause classes under a sibling `Exceptions/` folder. Each new class extends the existing per-namespace parent so `catch (Phalcon\<Namespace>\Exception $e)` continues to work unchanged. New classes:
    - `Phalcon\Acl\Exceptions`
        - `AccessRuleNotFound`
        - `CircularInheritanceError`
        - `ElementNotFound`
        - `ForbiddenWildcard`
        - `InvalidAccessList`
        - `InvalidComponentImplementation`
        - `InvalidRoleImplementation`
        - `InvalidRoleType`
        - `MissingFunctionParameters`
        - `ParameterTypeMismatch`
        - `RoleNotFoundException`
    - `Phalcon\Annotations\Exceptions`
        - `AnnotationNotFound`
        - `AnnotationsDirectoryNotWritable`
        - `CannotReadAnnotationData`
        - `UnknownAnnotationExpression`
    - `Phalcon\Application\Exceptions`
        - `ModuleNotRegistered`
    - `Phalcon\Assets\Exceptions`
        - `AssetSourceTargetCollision`
        - `CannotReadAsset`
        - `CollectionNotFound`
        - `InvalidAssetSourcePath`
        - `InvalidAssetTargetPath`
        - `InvalidFilter`
        - `InvalidTargetPath`
        - `TargetPathIsDirectory`
    - `Phalcon\Autoload\Exceptions`
        - `LoaderDirectoriesNotArray`
        - `LoaderMethodNotCallable`
    - `Phalcon\Cache\Exception`
        - `CacheKeysNotIterable`
        - `InvalidCacheKey`
    - `Phalcon\Cli\Console\Exceptions`
        - `ConsoleModuleNotRegistered`
        - `ContainerRequired`
        - `InvalidModuleDefinitionPath`
        - `ModuleDefinitionPathNotFound`
    - `Phalcon\Cli\Router\Exceptions`
        - `BeforeMatchNotCallable`
        - `InvalidRoutePaths`
        - `RouterArgumentsInvalidType`
    - `Phalcon\Config\Exceptions`
        - `CannotLoadConfigFile`
        - `ConfigNotArrayOrObject`
        - `GroupedAdapterRequiresArray`
        - `InvalidMergeData`
        - `MissingConfigOption`
        - `MissingFileExtension`
        - `MissingYamlExtension`
    - `Phalcon\DataMapper\Pdo\Exception`
        - `DriverNotSupported`
        - `UnknownDriverMethod`
        - `UnknownQueryMethod`
    - `Phalcon\Db\Exceptions`
        - `CannotInsertWithoutData`
        - `CannotPrepareStatement`
        - `CheckExpressionRequired`
        - `ColumnTypeRejectsAutoIncrement`
        - `ColumnTypeRejectsScale`
        - `ColumnTypeRequired`
        - `ConflictTargetColumnRequired`
        - `ConflictUpdateColumnRequired`
        - `ForeignKeyColumnsRequired`
        - `GeneratedAutoIncrementConflict`
        - `GeneratedDefaultConflict`
        - `IncompleteBindTypes`
        - `InvalidBindParameter`
        - `InvalidCheckExpression`
        - `InvalidGenerationExpression`
        - `InvalidGroupByExpression`
        - `InvalidIndexColumns`
        - `InvalidIndexDirections`
        - `InvalidIndexWhere`
        - `InvalidListExpression`
        - `InvalidOrderByExpression`
        - `InvalidSqlExpression`
        - `InvalidSqlExpressionType`
        - `InvalidUnaryExpression`
        - `InvalidWhereConditions`
        - `MatchedParameterNotFound`
        - `MaterializedViewsNotSupported`
        - `MissingDefinitionKey`
        - `MissingForeignKeyChecks`
        - `MissingSqliteDatabase`
        - `MysqlOnConflictNotSupported`
        - `NestedTransactionChangeBlocked`
        - `NoActiveTransaction`
        - `ReferencedColumnCountMismatch`
        - `ReferencedColumnsRequired`
        - `ReferencedTableRequired`
        - `ReturningNotSupported`
        - `ReturningRequiresColumn`
        - `SavepointsNotSupported`
        - `SqliteAlterCheckNotSupported`
        - `SqliteAlterColumnNotSupported`
        - `SqliteAlterForeignKeyNotSupported`
        - `SqliteAlterPrimaryKeyNotSupported`
        - `SqliteDropCheckNotSupported`
        - `SqliteDropForeignKeyNotSupported`
        - `SqliteDropPrimaryKeyNotSupported`
        - `TableMustHaveColumn`
        - `UnrecognizedDataType`
        - `UpdateFieldCountMismatch`
    - `Phalcon\Di\Exceptions`
        - `AliasAlreadyInUse`
        - `AliasNameMustBeString`
        - `ArgumentTypeRequired`
        - `CallArgumentsMustBeArray`
        - `CircularAliasReference`
        - `ContainerRequired`
        - `DefinitionMustBeArrayForRead`
        - `DefinitionMustBeArrayForUpdate`
        - `MethodCallMustBeArray`
        - `MethodNameRequired`
        - `MissingClassNameParameter`
        - `MissingParameterKey`
        - `PropertyInjectionRequiresInstance`
        - `PropertyMustBeArray`
        - `PropertyNameRequired`
        - `PropertyValueRequired`
        - `ServiceCannotBeResolved`
        - `SetterInjectionRequiresInstance`
        - `SetterParametersMustBeArray`
        - `UnknownServiceType`
    - `Phalcon\Dispatcher\Exceptions`
        - `ForwardInInitializeForbidden`
    - `Phalcon\Encryption\Crypt\Exception`
        - `DecryptionFailed`
        - `EmptyDecryptionKey`
        - `EmptyEncryptionKey`
        - `EncryptionFailed`
        - `InvalidPaddingSize`
        - `IvLengthCalculationFailed`
        - `MissingAuthData`
        - `MissingOpensslExtension`
        - `RandomBytesGenerationFailed`
        - `UnsupportedAlgorithm`
    - `Phalcon\Encryption\Security\Exceptions`
        - `InvalidRandomInput`
        - `UnknownHashAlgorithm`
    - `Phalcon\Encryption\Security\JWT\Exceptions`
        - `EmptyPassphrase`
        - `InvalidAudience`
        - `InvalidAudienceType`
        - `InvalidClaims`
        - `InvalidExpirationTime`
        - `InvalidHeader`
        - `InvalidNotBefore`
        - `MalformedJwtString`
        - `MissingJwtTypHeader`
        - `UnsupportedHmacAlgorithm`
        - `WeakPassphrase`
    - `Phalcon\Events\Exceptions`
        - `EventNotCancelable`
        - `InvalidEventHandler`
        - `InvalidEventSource`
        - `InvalidEventType`
        - `InvalidSubscriberConfiguration`
        - `NoListenersForEvent`
    - `Phalcon\Filter\Exceptions`
        - `FilterNotRegistered`
    - `Phalcon\Filter\Validation\Exceptions`
        - `FieldNotPrintable`
        - `FilterServiceUnavailable`
        - `InvalidAllowedTypes`
        - `InvalidCallbackReturn`
        - `InvalidDomainOption`
        - `InvalidFieldType`
        - `InvalidFilterService`
        - `InvalidStrictOption`
        - `InvalidValidationData`
        - `InvalidValidator`
        - `InvalidValidatorScope`
        - `MissingMbstring`
        - `NoDataToValidate`
        - `NoValidators`
        - `NoValidatorsInComposite`
        - `UniquenessConversionMustBeArray`
        - `UniquenessModelRequired`
        - `UniquenessOnlyForPhalconModel`
        - `ValidationEntityNotObject`
    - `Phalcon\Flash\Exceptions`
        - `EscaperServiceUnavailable`
        - `FlashMessageNotStringOrArray`
        - `SessionServiceUnavailable`
    - `Phalcon\Forms\Exceptions`
        - `ElementNotInForm`
        - `FormElementNameRequired`
        - `FormNotInLocator`
        - `FormNotRegistered`
        - `InvalidEntity`
        - `InvalidFilterType`
        - `InvalidJsonSchema`
        - `JsonSchemaNotArray`
        - `NoFormElements`
        - `SchemaEntryMissingKey`
        - `SchemaEntryNotArray`
        - `UnknownFormElementType`
        - `YamlExtensionRequired`
        - `YamlSchemaNotArray`
    - `Phalcon\Html\Exceptions`
        - `AttributeNotRenderable`
        - `FriendlyTitleConversionFailed`
        - `InvalidResultsetValue`
        - `ServiceNotRegistered`
        - `UsingRequiresTwoValues`
    - `Phalcon\Http\Cookie\Exceptions`
        - `CookieKeyTooShort`
        - `CryptInterfaceRequired`
        - `CryptServiceUnavailable`
        - `FilterServiceUnavailable`
    - `Phalcon\Http\Request\Exceptions`
        - `FilterServiceUnavailable`
        - `InvalidHost`
        - `InvalidHttpMethod`
        - `MissingFilters`
        - `SanitizerNotFound`
    - `Phalcon\Http\Response\Exceptions`
        - `NonStandardStatusCodeRequiresMessage`
        - `ResponseAlreadySent`
        - `ResponseServiceUnavailable`
        - `UrlServiceUnavailable`
    - `Phalcon\Image\Exceptions`
        - `CompositeFailed`
        - `ExtensionNotLoaded`
        - `ImageLoadFailed`
        - `MissingDimensions`
        - `MissingHeight`
        - `MissingWidth`
        - `ResizeFailed`
        - `ResourceTypeError`
        - `TextRenderingFailed`
        - `UnsupportedImageType`
        - `VersionMismatch`
    - `Phalcon\Logger\Adapter\Exceptions`
        - `FileOpenFailed`
        - `InvalidStreamMode`
        - `SyslogOpenFailed`
    - `Phalcon\Logger\Exceptions`
        - `AdapterNotFound`
        - `DeserializationFailed`
        - `NoAdaptersConfigured`
        - `SerializationFailed`
        - `TransactionAlreadyActive`
        - `TransactionNotActive`
    - `Phalcon\Messages\Exceptions`
        - `MessageNotObject`
        - `MessagesNotIterable`
    - `Phalcon\Mvc\Application\Exceptions`
        - `ContainerRequired`
        - `InvalidModuleDefinition`
        - `ModuleDefinitionPathNotFound`
    - `Phalcon\Mvc\Dispatcher\Exceptions`
        - `ResponseServiceUnavailable`
    - `Phalcon\Mvc\Micro\Exceptions`
        - `ContainerRequired`
        - `ErrorHandlerNotCallable`
        - `HandlerNotCallable`
        - `InvalidRegisteredHandler`
        - `LazyHandlerNotFound`
        - `MissingCollectionMainHandler`
        - `NoHandlersToMount`
        - `NoMatchedRouteHandler`
        - `NotFoundHandlerNotCallable`
        - `ResponseHandlerNotCallable`
    - `Phalcon\Mvc\Model\Behavior\Exceptions`
        - `MissingRequiredOption`
    - `Phalcon\Mvc\Model\Exceptions`
        - `BelongsToRequiresObject`
        - `BindTypeNotDefined`
        - `CannotResolveAttribute`
        - `ColumnNotInMap`
        - `ColumnNotInTableColumns`
        - `ColumnNotInTableMap`
        - `CorruptColumnType`
        - `CursorIsImmutable`
        - `DataTypeNotDefined`
        - `HandlerMustImplementBindable`
        - `IdentityNotInColumnMap`
        - `IdentityNotInTableColumns`
        - `IndexNotInCursor`
        - `IndexNotInRow`
        - `InvalidConnectionService`
        - `InvalidContainer`
        - `InvalidDumpResultKey`
        - `InvalidFindParameters`
        - `InvalidGetModelNameReturn`
        - `InvalidModelName`
        - `InvalidModelsManagerService`
        - `InvalidModelsMetadataService`
        - `InvalidResultsetCacheService`
        - `InvalidReturnedRecord`
        - `InvalidSerializationData`
        - `ManagerOrmServicesUnavailable`
        - `MethodNotFound`
        - `MissingMethodName`
        - `MissingModelClassName`
        - `ModelCouldNotLoad`
        - `ModelOrmServicesUnavailable`
        - `PrimaryKeyAttributeNotSet`
        - `PrimaryKeyRequired`
        - `PropertyNotAccessible`
        - `RecordCannotRefresh`
        - `RecordNotPersisted`
        - `ReferencedFieldsMismatch`
        - `RelationAliasMustBeString`
        - `RelationNotDefined`
        - `RelationRequiresObjectOrArray`
        - `ResultsetColumnNotInMap`
        - `RowIsImmutable`
        - `SnapshotsDisabled`
        - `StaticMethodRequiresOneArgument`
        - `UnknownRelationType`
        - `UpdateSnapshotDisabled`
    - `Phalcon\Mvc\Model\MetaData\Exceptions`
        - `CannotObtainTableColumns`
        - `ColumnMapNotArray`
        - `ContainerRequired`
        - `CorruptedMetaData`
        - `InvalidContainer`
        - `InvalidMetaDataForModel`
        - `MetaDataDirectoryNotWritable`
        - `MetaDataStrategyFailed`
        - `NoAnnotationsForClass`
        - `NoPropertyAnnotationsForClass`
        - `TableNotInDatabase`
    - `Phalcon\Mvc\Model\Query\Exceptions`
        - `AmbiguousColumn`
        - `AmbiguousJoinRelation`
        - `BindParameterNotInPlaceholders`
        - `BindTypeRequiresArray`
        - `BindValueRequired`
        - `ColumnNotInDomain`
        - `ColumnNotInSelectedModels`
        - `CorruptedAst`
        - `CorruptedDeleteAst`
        - `CorruptedInsertAst`
        - `CorruptedSelectAst`
        - `CorruptedUpdateAst`
        - `DeleteMultipleNotSupported`
        - `DuplicateAlias`
        - `EmptyArrayPlaceholderValue`
        - `InsertColumnCountMismatch`
        - `InvalidCachedResultset`
        - `InvalidCachingOptions`
        - `InvalidColumnDefinition`
        - `InvalidInjectedManager`
        - `InvalidInjectedMetadata`
        - `InvalidQueryCacheService`
        - `InvalidResultsetClass`
        - `JoinAliasAlreadyUsed`
        - `JoinFieldCountMismatch`
        - `MissingCacheKey`
        - `MissingMetaData`
        - `MissingModelAttribute`
        - `MissingModelsManager`
        - `MixedDatabaseSystems`
        - `ModelSourceNotFound`
        - `ModelsListNotLoaded`
        - `MultipleSqlStatementsNotSupported`
        - `NoModelForAlias`
        - `PhqlColumnNotInMap`
        - `ReadConnectionMissing`
        - `RelationshipNotFound`
        - `ResultsetClassNotFound`
        - `ResultsetNonCacheable`
        - `UnknownBindType`
        - `UnknownColumnType`
        - `UnknownJoinType`
        - `UnknownModelOrAlias`
        - `UnknownPhqlExpression`
        - `UnknownPhqlExpressionType`
        - `UnknownPhqlStatement`
        - `UpdateMultipleNotSupported`
        - `WriteConnectionMissing`
    - `Phalcon\Mvc\Model\Query\Exceptions\Builder`
        - `BuilderColumnNotInMap`
        - `BuilderConditionInvalid`
        - `ModelRequired`
        - `NoPrimaryKey`
        - `OperatorNotAvailable`
    - `Phalcon\Mvc\Router\Exceptions`
        - `AnnotationsServiceUnavailable`
        - `BeforeMatchNotCallable`
        - `ConfigKeyMustBeArray`
        - `EmptyGroupOfRoutes`
        - `GroupRoutesMustBeArray`
        - `InvalidCallbackParameter`
        - `InvalidConfigSource`
        - `InvalidNotFoundPaths`
        - `InvalidRoutePaths`
        - `InvalidRoutePosition`
        - `InvalidRouterFactoryConfig`
        - `MissingGroupRouteKey`
        - `MissingRouteConfigKey`
        - `RequestServiceUnavailable`
        - `UnknownHttpMethod`
        - `WrongPathsKey`
    - `Phalcon\Mvc\Url\Exceptions`
        - `MissingRouteName`
        - `RouteNotFound`
        - `RouterServiceUnavailable`
    - `Phalcon\Mvc\View\Engine\Volt\Exceptions`
        - `CannotOpenCompiledFile`
        - `CorruptedStatement`
        - `CorruptedStatementWithData`
        - `InvalidCompilationPrefix`
        - `InvalidExtension`
        - `InvalidHaystack`
        - `InvalidIntermediateRepresentation`
        - `InvalidOptionType`
        - `InvalidPathClosureReturn`
        - `InvalidPathType`
        - `InvalidStatement`
        - `InvalidUserFilterDefinition`
        - `InvalidUserFunctionDefinition`
        - `MacroAlreadyDefined`
        - `MacroNotFound`
        - `MbstringRequired`
        - `TemplateFileNotFound`
        - `TemplateFileNotOpenable`
        - `TemplatePathCollision`
        - `UnknownVoltExpression`
        - `UnknownVoltFilter`
        - `UnknownVoltFilterType`
        - `UnknownVoltStatement`
        - `VoltDirectoryNotWritable`
    - `Phalcon\Mvc\View\Exceptions`
        - `InvalidEngineRegistration`
        - `InvalidViewsDirType`
        - `SimpleViewNotFound`
        - `SimpleViewServicesUnavailable`
        - `ViewNotFound`
        - `ViewServicesUnavailable`
        - `ViewsDirItemMustBeString`
    - `Phalcon\Paginator\Exceptions`
        - `BuilderModelNotDefined`
        - `InvalidBuilderInstance`
        - `InvalidCursorColumn`
        - `InvalidLimit`
        - `MissingColumnsForHaving`
        - `MissingRequiredParameter`
        - `PaginatorDataNotArray`
    - `Phalcon\Session\Adapter\Exceptions`
        - `AdapterRuntimeError`
        - `InvalidSavePath`
        - `SavePathUnavailable`
    - `Phalcon\Session\Exceptions`
        - `InvalidSessionAdapter`
        - `InvalidSessionName`
        - `SessionAlreadyStarted`
        - `SessionModificationDenied`
    - `Phalcon\Storage\Exceptions`
        - `AuthenticationFailed`
        - `ClusterConnectionFailed`
        - `ConnectionFailed`
        - `DatabaseSelectionFailed`
        - `InvalidConfiguration`
        - `StorageError`
    - `Phalcon\Storage\Serializer\Exceptions`
        - `InvalidSerializationInput`
        - `InvalidUnserializationInput`
    - `Phalcon\Support\Collection\Exceptions`
        - `InvalidValueType`
        - `ReadOnlyViolation`
    - `Phalcon\Support\Debug\Exceptions`
        - `RequestHalted`
        - `RuntimeWarning`
    - `Phalcon\Support\Helper\Json\Exceptions`
        - `JsonDecodeError`
        - `JsonEncodeError`
    - `Phalcon\Support\Helper\Str\Exceptions`
        - `InsufficientArguments`
        - `InvalidReplaceFormat`
        - `SyntaxError`
    - `Phalcon\Time\Clock\Exceptions`
        - `InvalidModifier`
    - `Phalcon\Translate\Exceptions`
        - `FileOpenError`
        - `ImmutableObject`
        - `InterpolatorNotRegistered`
        - `InvalidDataType`
        - `KeyNotFound`
        - `MissingContent`
        - `MissingGettextExtension`
        - `MissingRequiredParameter`
        - `TranslatorNotRegistered` [#17019](https://github.com/phalcon/cphalcon/issues/17019)
- Added `Phalcon\Events\Manager::fire()` `beforeFire()` / `afterFire()` extension seams to `Manager::fire()`. [#17065](https://github.com/phalcon/cphalcon/issues/17065) [[doc]](https://docs.phalcon.io/5.14/events/)
- Added opt-in memory caps for long-running workers (Swoole / RoadRunner / queue consumers). Default `0` preserves the original unbounded behavior: [#17049](https://github.com/phalcon/cphalcon/issues/17049)
    - `Phalcon\Db\Profiler::setMaxProfiles(int)` / `getMaxProfiles()` [[doc]](https://docs.phalcon.io/5.14/db-layer/)
    - `Phalcon\Logger\Adapter\AbstractAdapter::setQueueLimit(int)` / `getQueueLimit()` [[doc]](https://docs.phalcon.io/5.14/logger/)
    - `Phalcon\Events\Manager::setMethodExistsCacheLimit(int)` / `getMethodExistsCacheLimit()` [[doc]](https://docs.phalcon.io/5.14/events/)
    - `Phalcon\Annotations\Adapter\AbstractAdapter::setAnnotationsLimit(int)` / `getAnnotationsLimit()` [[doc]](https://docs.phalcon.io/5.14/annotations/)
    - `Phalcon\Storage\Adapter\Memory::setMaxItems(int)` / `getMaxItems()` [[doc]](https://docs.phalcon.io/5.14/storage/)
- Added `Phalcon\Mvc\Router\Route::setRouteId(string $routeId)` - setter intended for restoring cached routes [#17012](https://github.com/phalcon/cphalcon/issues/17012) [[doc]](https://docs.phalcon.io/5.14/routing/)
- Added `Phalcon\Mvc\Router::buildDispatcherDump()` / `Phalcon\Mvc\Router::loadDispatcherFromArray(array $dump)` - used to build/load the routes [#17012](https://github.com/phalcon/cphalcon/issues/17012) [[doc]](https://docs.phalcon.io/5.14/routing/)
- Added `Phalcon\Mvc\Router::dumpDispatcher(string $path)` / `Phalcon\Mvc\Router::loadDispatcher(string $path)` - file-shaped helpers that write/read routes [#17012](https://github.com/phalcon/cphalcon/issues/17012) [[doc]](https://docs.phalcon.io/5.14/routing/)
- Added `Phalcon\Mvc\Router::useCache()` - to use a `Phalcon\Cache` adapter to store routes [#17012](https://github.com/phalcon/cphalcon/issues/17012) [[doc]](https://docs.phalcon.io/5.14/routing/)

### Fixed

- Fixed `$this->eventsManager` resolving to `null` inside `Phalcon\Mvc\Controller` methods [#17060](https://github.com/phalcon/cphalcon/issues/17060) [[doc]](https://docs.phalcon.io/5.14/controllers/)
- Fixed `Phalcon\Events\Event` and `Phalcon\Events\Manager::fire()` being declared `final` in 5.13.0 ([#17006](https://github.com/phalcon/cphalcon/issues/17006)), which prevented subclassing the events manager. [#17065](https://github.com/phalcon/cphalcon/issues/17065) [[doc]](https://docs.phalcon.io/5.14/events/)
- Fixed `Phalcon\Forms\Form::clear()` leaving a previously-bound `null` field value in the data array instead of unsetting it before reassigning the element default [#17042](https://github.com/phalcon/cphalcon/issues/17042) [[doc]](https://docs.phalcon.io/5.14/forms/)
- Fixed `Phalcon\Mvc\Model::getChangedFields()` / `hasChanged()` flagging every null-valued column of a freshly-loaded row as changed [#17042](https://github.com/phalcon/cphalcon/issues/17042) [[doc]](https://docs.phalcon.io/5.14/db-models/)
- Fixed `Phalcon\Mvc\Model::getUpdatedFields()` flagging unchanged null-valued columns as updated [#17042](https://github.com/phalcon/cphalcon/issues/17042) [[doc]](https://docs.phalcon.io/5.14/db-models/)
- Fixed `Phalcon\Mvc\Model\Row::offsetGet()` / `offsetExists()` throwing `The index does not exist in the row` when accessing a column whose value is `null` [#17041](https://github.com/phalcon/cphalcon/issues/17041) [[doc]](https://docs.phalcon.io/5.14/db-models/)
- Fixed `Phalcon\Mvc\Router::handle()` falling back to a default catch-all route instead of matching an HTTP-method-constrained route attached afterward. [#17062](https://github.com/phalcon/cphalcon/issues/17062) [[doc]](https://docs.phalcon.io/5.14/routing/)
- Fixed `Phalcon\Mvc\View::getContent()` throwing `TypeError` after `View::start()`. `start()` was assigning `$this->content = null` [#17041](https://github.com/phalcon/cphalcon/issues/17041) [[doc]](https://docs.phalcon.io/5.14/views/)
- Fixed `Phalcon\Mvc\View\Engine\Volt\Compiler` emitting invalid PHP when a double-quoted Volt string contained literal single quotes (e.g. `"send_ga('Link', ...)"`). Only un-escaped single quotes are now escaped, so the `'Let\'s Encrypt'` case from [#17002](https://github.com/phalcon/cphalcon/issues/17002) is preserved [#17046](https://github.com/phalcon/cphalcon/issues/17046) [[doc]](https://docs.phalcon.io/5.14/volt/)
- Fixed warning in `Phalcon\Auth\ManagerFactory` emitted in tests (random commit but had to be fixed) [#17066](https://github.com/phalcon/cphalcon/pull/17066)

### Removed

- Removed unused `Phalcon\Contracts\Container\Service\Lifetime` [#17066](https://github.com/phalcon/cphalcon/pull/17066)
- Reverted the `Phalcon\Mvc\Model\Query::executeUpdate()` named-placeholder substitution introduced for [#16976](https://github.com/phalcon/cphalcon/issues/16976). The substitution path was triggering a use-after-free in the model update flow under PostgreSQL ([#17042](https://github.com/phalcon/cphalcon/issues/17042)). Issue [#16976](https://github.com/phalcon/cphalcon/issues/16976) is reopened and will be addressed with a different approach. [[doc]](https://docs.phalcon.io/5.14/db-phql/)

## [5.13.0](https://github.com/phalcon/cphalcon/releases/tag/v5.13.0) (2026-05-18)

### Tools

- Zephir Parser v1.8.0
- Zephir 0.20.1 (development - 6c2a72925)

### Changed

- Changed `Phalcon\Contracts\Support\Collection` to declare the expanded method surface (`column`, `each`, `filter`, `first`, `getType`, `isEmpty`, `keys`, `last`, `map`, `reduce`, `replace`, `sort`, `values`, `where`) so the contract matches the implementation [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Changed `Phalcon\Events\Event` to be declared `final`. The class is a value object holding `type`, `source`, `data`, `cancelable`, and `stopped`; no subclasses exist in the cphalcon tree and any future typed-event work would add new sibling classes implementing `EventInterface` rather than extending `Event`. Marking it `final` lets the C extension fold the per-fire getters (`getType`, `getSource`, `getData`, `isCancelable`, `isStopped`, `isPropagationStopped`) into direct dispatch. BC note: any userland `class MyEvent extends Event` now fails [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager::attach()` to classify the handler kind once at attach time so the dispatch loop can route via a single branch instead of running `instanceof Closure` / `is_callable` / `typeof` per fire per listener. Four kinds are recognized: `0` = Closure (direct invocation via Zephir's `{handler}(args)` syntax, no arg-array allocation), `1` = `[obj, method]` array callable (direct dynamic dispatch `handler[0]->{handler[1]}(args)`, no `call_user_func_array` overhead), `2` = plain object with method named after the event - the classic Phalcon listener pattern (class name is captured at attach time and stored on the tuple to skip `get_class()` per fire), `3` = generic callable (string function name, invokable object, `[class, staticMethod]`) routed through `call_user_func_array`. The subscriber array-form attach paths (`[methodName, priority]` and `[[methodA, priorityA], [methodB, priorityB]]`) now route through `insertHandlerEntry` directly with `kind=1`, bypassing the classification cascade since the resulting handler shape is already known. `methodExistsCache` access in the dispatch loop is tightened to a single `isset` fast path [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager::detach()` to drop the `eventType` key entirely when its queue empties (removing the last listener), so `hasListeners()` and `fire()`'s short-circuit tell the truth. Previously an emptied queue left the key in place with an empty array value, causing `isset($this->events[$eventType])` to return `true` with no actual listeners to dispatch to. The matching `DetachTest` expectations are updated to reflect the new contract [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager::detachAll(null)` to reset `events` to `[]` instead of `null`. The previous `null` reset broke `isset($this->events[$key])` semantics inside `attach()` and `fire()` until the next assignment refilled the property; the empty-array form keeps all access paths consistent [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager::fire()` and `Phalcon\Events\Manager::fireAll()` to wrap dispatch in `try { ... } catch \Throwable, ex { cleanup; throw ex; }`. A throwing listener restores stashed responses (if nested) and decrements `fireDepth` back to its pre-call value before the exception propagates, so manager state stays clean for the next fire - important for long-lived managers (workers, daemons) where a single dirty teardown would poison every subsequent fire [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager::fire()` to parse the `eventType` only once per unique string and cache the result (`[typePrefix, eventName]`) in an internal `eventNameCache` keyed by the original string. Repeated fires of the same event name (`db:beforeQuery × N` per request, model lifecycle events on hot save paths) collapse to a single hash lookup after warm-up. The cache never needs invalidation because the parse is deterministic for a given input string. `fire()` also short-circuits before allocating the `Event` instance when no listeners match either the type queue or the fully-qualified queue - in production most fires have zero matching listeners (model lifecycle events with no user-attached behavior, DB events without a tracer), so the avoided allocation is significant in hot paths [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager::fire()`, `Phalcon\Events\Manager::attach()`, and `Phalcon\Events\Manager::fireQueue()` to be declared `final`. The class itself stays open so genuine subclasses that only add new methods continue to work, but the dispatch hot path is locked to enable C-level direct dispatch on the three methods that run per-event. The remaining public surface (`addSubscriber`, `removeSubscriber`, `detach`, `detachAll`, `getListeners`, `getResponses`, `getSubscribers`, `hasListeners`, `isCollecting`, `isValidHandler`, `collectResponses`, `enablePriorities`, `arePrioritiesEnabled`, `isStrict`, `setStrict`, `fireAll`, `clearSubscribers`) is left non-`final` so decorator-style subclasses that wrap these less-hot methods can still override them. BC note: subclasses that override `fire`, `attach`, or `fireQueue` now fail [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager::fireQueue()` to be a thin BC-preserving wrapper around a new private `Phalcon\Events\Manager::dispatch()` helper. The public signature `fireQueue(array $queue, EventInterface $event)` is unchanged; the framework's own `fire()` path bypasses `fireQueue` and calls `dispatch()` directly with hoisted arguments (`eventName`, `source`, `data`, `cancelable`, `collect`) so the second dispatch leg of a two-queue `fire()` (type queue + fully-qualified queue) does not re-extract identical values from the event. The dispatch path also applies a single-handler fast path: when the queue has exactly one listener (very common in unit tests and lightly-instrumented production), the foreach plus per-iteration cancelable/isStopped check is skipped and dispatch goes straight through the type-branch [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager` dispatch return-value contract to **last non-null wins**. Previously every listener return overwrote the running `status`, so a chain of (`"value"`, `null`) ended with `fire()` returning `null` and silently losing the earlier real value. The new contract only updates `status` when the listener return is non-null - the last meaningful return survives. The `stop()` determinism rule overrides last-non-null: when a listener calls `$event->stop()` (and `cancelable=true`), that listener's return value is what `fire()` returns - even if `null` - because the caller asked for the stopping listener's verdict explicitly. Returning `false` from a listener does **not** short-circuit the queue; callers wanting to stop downstream listeners must call `$event->stop()`. Consumers like `Phalcon\Mvc\Dispatcher` that interpret a `false` return from `fire()` as a cancel signal are unaffected because that check happens in their own dispatch logic, not in the events manager [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Events\Manager` listener storage from `SplPriorityQueue` to a sorted array of `[handler, type, priority]` tuples (with an additional `className` element on `type=2` tuples). The `SplPriorityQueue::EXTR_BOTH` clone-per-fire and O(n) `setExtractFlags()` rebuild on detach are eliminated; the "empty heap" warnings produced by `SplPriorityQueue` on never-fired event types disappear as a side effect. Insert order under the same priority is preserved (FIFO). When `enablePriorities` is off (the default), `insertHandlerEntry` short-circuits to a plain append - the sorted-insert loop only runs when priorities are explicitly enabled. When it does run, the insert uses `array_splice` instead of a per-element rebuild [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Changed `Phalcon\Html\Escaper` into a façade over five per-context escapers (`Phalcon\Html\Escaper\HtmlEscaper`, `AttributeEscaper`, `CssEscaper`, `JsEscaper`, `UrlEscaper`); each is reachable via `getXxxEscaper()`/`setXxxEscaper()` so individual contexts can be swapped without subclassing the façade. The legacy `setEncoding()`, `setFlags()`, and `setDoubleEncode()` setters fan out to all sub-escapers so existing code keeps working [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-escaper/)
- Changed `Phalcon\Html\Helper\AbstractSeries::__toString()` to `ksort()` its `store` before rendering so entries are emitted in position order rather than registration order. [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Changed `Phalcon\Html\Helper\Input\Checkbox` and `Phalcon\Html\Helper\Input\Radio` to extend a new shared `Phalcon\Html\Helper\Input\AbstractChecked`; `Radio` no longer extends `Checkbox`. Two paths now render `checked="checked"`: the unconditional opt-ins `["checked" => "checked"]` (case-insensitive) and `["checked" => true]`, and a value-match path comparing the supplied `checked` attribute against the input's `value` (`==` by default for mixed int/string round-tripping, `===` under `strict(true)`) [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Changed `Phalcon\Html\Helper\Style::add()` and `Phalcon\Html\Helper\Script::add()` now accept an optional `int $position = -1` argument that is routed through the new protected `pushOrPlace()` helper (negative pushes onto the next auto-increment slot, non-negative places at that key, advancing past occupied slots) [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Changed `Phalcon\Html\TagFactory` to no longer extend `Phalcon\Factory\AbstractFactory`; the registration pipeline now accepts class-strings, closures/callables, or `[className, [depKey, ...]]` / `[className, [depKey, ...], [extraArg, ...]]` tuples, and helpers are lazily cached per name in a separate `instances` map so `set()` overrides correctly invalidate the previous instance. `has()` now reports against the recipe map instead of the resolved-instance map [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Changed `Phalcon\Mvc\Model\Resultset\Complex::current()` LEFT-JOIN hydration to be controlled by the new `orm.resultset_empty_left_join_model` ini setting (default `true`). When the flag is `true` (default) a LEFT JOIN that matches no row hydrates an empty Model instance whose every column is `null` - preserving the pre-5.13 behavior so applications upgrading from 5.9.x do not need code changes. Setting the flag to `false` (via `php.ini`, `.htaccess`, or `Phalcon\Support\Settings::set('orm.resultset_empty_left_join_model', false)`) restores the 5.13.0 contract introduced by [#16239](https://github.com/phalcon/cphalcon/issues/16239), where the unmatched slot is plainly `null`. The new key is wired into `Phalcon\Support\Settings::get()` / `set()` alongside the other `orm.*` toggles [#16960](https://github.com/phalcon/cphalcon/issues/16960) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Changed `Phalcon\Mvc\Url\UrlInterface::get()` signature to match the implementation: the previously undeclared `var baseUri = null` fourth parameter (added to `Phalcon\Mvc\Url::get()` in 2015 but never propagated to the interface) and the new `bool replaceArgs = false` fifth parameter are now part of the contract [#16986](https://github.com/phalcon/cphalcon/issues/16986) [[doc]](https://docs.phalcon.io/5.13/mvc-url/)
- Changed `Phalcon\Support\Collection::__construct()` to accept two additional opt-in parameters - `bool $strictNull = false` and `?string $type = null`. When `strictNull` is `true`, `get()` returns stored `null` values verbatim instead of substituting the default (previous behavior was unconditional default-substitution); when `type` is set, every `set()`/`init()` runs through the new `validateType()` guard, which maps the scalar tokens `int`/`string`/`bool`/`float`/`array`/`object` to their `is_*` checks and treats anything else as a class/interface name compared with `instanceof`, throwing `InvalidArgumentException` on mismatch [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Changed `Phalcon\Support\Collection::__serialize()` to return a structured array `["data", "insensitive", "strictNull", "type"]` so a serialize/unserialize round-trip restores the full configuration (previously only `data` survived). `__unserialize()` accepts both the new structured format and the legacy flat-array format, so existing serialized blobs keep deserializing without intervention [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Changed `Phalcon\Support\Collection::getKeys()` and `Phalcon\Support\Collection::getValues()` to delegate to the new `keys()` / `values()` methods and marked the `get*`-prefixed pair `@deprecated`; behavior is unchanged but new code should call `keys()` / `values()` instead [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)

### Added

- Added CHECK-constraint support via new `Phalcon\Db\Check` value object and `Phalcon\Db\CheckInterface`. `Check` takes a constraint name (string; empty string means an unnamed constraint, in which case the dialect omits the `CONSTRAINT <name>` prefix) and a definition array containing the required `expression` key (the boolean SQL predicate). `Phalcon\Db\Dialect\Mysql`, `Phalcon\Db\Dialect\Postgresql`, and `Phalcon\Db\Dialect\Sqlite` all recognize `definition["checks"]` (array of `CheckInterface`) inside `createTable()` and emit an inline `[CONSTRAINT "<name>"] CHECK (<expr>)` line alongside the column/index/reference lines. New dialect methods `addCheck()` and `dropCheck()` emit the equivalent `ALTER TABLE ... ADD CONSTRAINT ... CHECK (...)` and `ALTER TABLE ... DROP CHECK|CONSTRAINT ...` SQL for MySQL 8.0.16+ and PostgreSQL; SQLite throws (CHECK constraints can only be declared at CREATE TABLE time in SQLite, the same limitation that already applies to FK constraints). New adapter methods `Phalcon\Db\Adapter\AbstractAdapter::addCheck()` and `Phalcon\Db\Adapter\AbstractAdapter::dropCheck()` provide the symmetric one-call ergonomics already available for `addForeignKey()` / `dropForeignKey()`. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added MySQL 8.0+ `INVISIBLE` index support to `Phalcon\Db\Index`. The constructor's second parameter is now backward-compatibly overloaded: passing a plain list of column names continues to work (legacy positional form), while passing an associative array containing a `columns` key activates the new definition-array form (`["columns" => [...], "type" => "...", "invisible" => true]`). The third positional `type` argument is honored only when the second argument is the legacy list form; in definition-array mode `type` comes from the array. `Index` gains a matching `isInvisible(): bool` accessor and throws a `Phalcon\Db\Exception` if the definition-array path is taken but `columns` is not itself an array. `Phalcon\Db\Dialect\Mysql::addIndex` and `Phalcon\Db\Dialect\Mysql::createTable` emit a trailing ` INVISIBLE` keyword for invisible indexes. `Phalcon\Db\Adapter\Pdo\Mysql::describeIndexes` reverse-engineers the flag from the MySQL 8.0+ `Visible` column of `SHOW INDEXES` (absent on 5.7, which defaults to visible). PostgreSQL and SQLite have no equivalent and their dialects ignore the flag. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added MySQL 8.0.23+ `INVISIBLE` column support to `Phalcon\Db\Column`. A new boolean definition-array key `invisible` (default `false`) is parsed by the constructor; a matching `isInvisible(): bool` accessor reports the state at runtime. `Phalcon\Db\Dialect\Mysql::addColumn`, `Phalcon\Db\Dialect\Mysql::createTable`, and `Phalcon\Db\Dialect\Mysql::modifyColumn` emit ` INVISIBLE` after the NOT NULL/NULL clause when the flag is set. PostgreSQL and SQLite have no equivalent and their dialects ignore the flag. `Phalcon\Db\Adapter\Pdo\Mysql::describeColumns` reverse-engineers the flag from the `EXTRA` column of `information_schema.COLUMNS` (already in the result set since item #1's switch from `SHOW FULL COLUMNS`) - substring-matched so the flag is still detected when MySQL concatenates it with other extras like `INVISIBLE STORED GENERATED`. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added PostgreSQL `CREATE INDEX CONCURRENTLY` support via a new `concurrently` definition-array key on `Phalcon\Db\Index` (default `false`). `Phalcon\Db\Index::isConcurrent(): bool` exposes the flag at runtime. `Phalcon\Db\Dialect\Postgresql::addIndex` now emits `CONCURRENTLY` between the `INDEX` keyword and the index name when the flag is set (`CREATE INDEX CONCURRENTLY "idx_email" ON "schema"."table" (...)`), so the index can be built without taking the strong lock that normally blocks writers. MySQL and SQLite have no equivalent concept and their dialects ignore the flag. No reverse engineering is meaningful (the option is creation-time only - once built, the index is indistinguishable from one built non-concurrently). [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added PostgreSQL materialized-view support to the Db dialect and adapter layers. Three new methods land on `Phalcon\Db\Dialect`: `createMaterializedView(string $viewName, array $definition, string $schemaName = null): string` (definition takes a required `sql` key, same shape as `createView`), `dropMaterializedView(string $viewName, string $schemaName = null, bool $ifExists = true): string`, and `refreshMaterializedView(string $viewName, string $schemaName = null, bool $concurrent = false): string` (passing `$concurrent = true` emits `REFRESH MATERIALIZED VIEW CONCURRENTLY ...` for non-blocking refresh - requires a unique index on the view). `Phalcon\Db\Dialect\Postgresql` overrides all three to emit the correct SQL; the base implementations throw `Phalcon\Db\Exception`, which is inherited unchanged by `Phalcon\Db\Dialect\Mysql` and `Phalcon\Db\Dialect\Sqlite` (neither engine has a materialized-view concept). `Phalcon\Db\Adapter\AbstractAdapter` gains three matching `createMaterializedView()` / `dropMaterializedView()` / `refreshMaterializedView()` wrappers that execute the dialect-built SQL and return bool.  [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added PostgreSQL-specific column types and array-column support to `Phalcon\Db\Column` and `Phalcon\Db\Dialect\Postgresql`. Ten new `Column::TYPE_*` constants are introduced: `TYPE_BYTEA` (30), `TYPE_INET` (31), `TYPE_CIDR` (32), `TYPE_MACADDR` (33), `TYPE_INT4RANGE` (34), `TYPE_INT8RANGE` (35), `TYPE_NUMRANGE` (36), `TYPE_TSRANGE` (37), `TYPE_TSTZRANGE` (38), `TYPE_DATERANGE` (39). `Phalcon\Db\Dialect\Postgresql::getColumnDefinition` recognizes the new types and emits the matching keywords (`BYTEA`, `INET`, `CIDR`, `MACADDR`, `INT4RANGE`, `INT8RANGE`, `NUMRANGE`, `TSRANGE`, `TSTZRANGE`, `DATERANGE`). MySQL and SQLite dialects fall through their existing `default` branches for these constants - users targeting those engines should pick a portable base type instead. Additionally, a new boolean definition-array key `array` and a `Phalcon\Db\Column::isArray(): bool` accessor expose array-column intent; when `isArray()` is `true`, the PostgreSQL dialect appends `[]` to the type (`INTEGER[]`, `TEXT[]`, `INET[]`, etc.). MySQL and SQLite ignore the flag. `Phalcon\Db\Adapter\Pdo\Postgresql::describeColumns` reverse-engineers the new types by matching the `data_type` column from `information_schema.columns` and sets `array` when `data_type` reports `ARRAY` or contains `[]`. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added `FOR SHARE` shared-lock emission to `Phalcon\Db\Dialect\Postgresql::sharedLock()`; it previously returned the original query unchanged (silent no-op), so callers had no way to express PostgreSQL's row-level shared lock through the cphalcon dialect API. The method now appends `" FOR SHARE"` and also accepts the optional `string $modifier = ""` second argument introduced by item #3, so callers can request `FOR SHARE NOWAIT` / `FOR SHARE SKIP LOCKED` via the `Phalcon\Contracts\Db\Dialect::LOCK_NOWAIT` / `LOCK_SKIP_LOCKED` constants. The signature change is propagated to `Phalcon\Contracts\Db\Dialect::sharedLock`, `Phalcon\Contracts\Db\Adapter\Adapter::sharedLock`, and the SQLite and MySQL impls - SQLite remains a no-op regardless of the modifier (no row-level locking), and MySQL still emits its legacy `LOCK IN SHARE MODE` and silently ignores any modifier (the legacy syntax does not support `NOWAIT` / `SKIP LOCKED`; users on MySQL 8.0+ who need those modifiers can use `forUpdate()` instead). `Phalcon\Db\Adapter\AbstractAdapter::sharedLock` passes the modifier through to the dialect [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added `NOWAIT` / `SKIP LOCKED` row-lock modifiers to `forUpdate()`. The dialect and adapter `forUpdate()` methods now accept an optional second `string $modifier = ""` argument; pass one of the new contract constants `Phalcon\Contracts\Db\Dialect::LOCK_NONE` (default), `Phalcon\Contracts\Db\Dialect::LOCK_NOWAIT`, or `Phalcon\Contracts\Db\Dialect::LOCK_SKIP_LOCKED` to emit `SELECT ... FOR UPDATE`, `SELECT ... FOR UPDATE NOWAIT`, or `SELECT ... FOR UPDATE SKIP LOCKED` respectively. Recognized by MySQL 8.0+ and PostgreSQL 9.5+; SQLite has no row-level locking and silently ignores the modifier. Signature change is propagated to `Phalcon\Contracts\Db\Dialect::forUpdate`, `Phalcon\Contracts\Db\Adapter\Adapter::forUpdate`, `Phalcon\Db\Dialect::forUpdate` (base), `Phalcon\Db\Dialect\Sqlite::forUpdate` (override remains a no-op), and `Phalcon\Db\Adapter\AbstractAdapter::forUpdate` (pass-through). Existing single-argument call sites are unaffected - the second parameter defaults to `""` [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added `ON CONFLICT (...) DO UPDATE SET ...` upsert support to the Db dialect and adapter layers. New SQL-transformer method `Phalcon\Db\Dialect::onConflictUpdate(string $sqlQuery, array $conflictColumns, array $updateColumns): string` appends an upsert clause to the supplied INSERT statement using the SQL-standard form recognized by PostgreSQL 9.5+ and SQLite 3.24+: `INSERT INTO ... ON CONFLICT ("col") DO UPDATE SET "other" = excluded."other"`. The base implementation in `Phalcon\Db\Dialect::onConflictUpdate` provides the standard emission (inherited by `Phalcon\Db\Dialect\Postgresql` and `Phalcon\Db\Dialect\Sqlite`); `Phalcon\Db\Dialect\Mysql::onConflictUpdate` overrides to throw because MySQL's `INSERT ... ON DUPLICATE KEY UPDATE` shape is incompatible (deferred to parser item #23). Throws `Phalcon\Db\Exception` when either the `conflictColumns` or `updateColumns` array is empty. `Phalcon\Db\Adapter\AbstractAdapter::onConflictUpdate` provides the symmetric one-call passthrough. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added `Phalcon\Contracts\Db` namespace housing the canonical contracts for the Db layer: `Phalcon\Contracts\Db\Check`, `Phalcon\Contracts\Db\Column`, `Phalcon\Contracts\Db\Dialect`, `Phalcon\Contracts\Db\Index`, `Phalcon\Contracts\Db\Reference`, `Phalcon\Contracts\Db\Result`, and `Phalcon\Contracts\Db\Adapter\Adapter`. The legacy interfaces (`Phalcon\Db\CheckInterface`, `Phalcon\Db\ColumnInterface`, `Phalcon\Db\DialectInterface`, `Phalcon\Db\IndexInterface`, `Phalcon\Db\ReferenceInterface`, `Phalcon\Db\ResultInterface`, `Phalcon\Db\Adapter\AdapterInterface`) are kept as thin extensions of their contract counterparts, marked `@deprecated` (matching the `Phalcon\Support\Collection\CollectionInterface` migration pattern), so existing implementors and typehints continue to work in the v5 line. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added `Phalcon\Contracts\Events\Stoppable` - a Phalcon-owned mirror of PSR-14's `StoppableEventInterface` with the single `isPropagationStopped(): bool` method. `Phalcon\Events\Event` implements it and routes the call through the same internal `stopped` flag as `isStopped()`, so existing `$event->stop()` callers automatically expose the PSR-14-shaped accessor without changing their code. Keeps PSR types out of the extension while letting a future `phalcon/events-psr14-bridge` package map Phalcon ↔ PSR-14 in PHP land [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Added `Phalcon\Contracts\Events\Subscriber` - a subscriber contract requiring a single static `getSubscribedEvents(): array` method. Supported shapes per entry: `['event:name' => 'methodName']` (plain string), `['event:name' => ['methodName', priority]]` (method with priority), or `['event:name' => [['methodA', priorityA], ['methodB', priorityB]]]` (multiple listeners per event). Replaces the magic-method-only routing as the primary discoverability mechanism while keeping that path intact for BC [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Added `Phalcon\Contracts\Forms\Schema` interface defining a single `load(): array` method for objects that supply a normalized list of form-element definitions [#16996](https://github.com/phalcon/cphalcon/issues/16996) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Added `Phalcon\Contracts\Mvc\Model\Relation\CacheKeyProvider` with a single `getUniqueKey(): string` method; when a model implements this interface and a relation is marked `reusable => true`, the Model Manager uses the return value of `getUniqueKey()` as the cache key instead of the object-identity-based `unique_key()` builtin, allowing different PHP object instances that represent the same database row to share the same reusable record cache entry [#16993](https://github.com/phalcon/cphalcon/issues/16993) [[doc]](https://docs.phalcon.io/5.13/db-models-relationships/)
- Added `Phalcon\Di\Di::hasShared(string $name): bool` and `Phalcon\Di\Di::removeShared(string $name): void` (also declared on `Phalcon\Di\DiInterface`) to operate on the shared-instance cache independently of the service-definition registry. `hasShared()` reports whether `getShared()` has already materialized an instance for the given name (in contrast to `has()`, which reports on the definition registry). `removeShared()` drops the cached instance - both from `Di::$sharedInstances` and from the `Service` object's internal `sharedInstance` field - without removing the service definition, so the next `getShared()` call rebuilds a fresh instance. Use case: fork-based multi-process workers that need to discard the parent's inherited resource handle (DB connection, etc.) without re-registering the service. Both methods are alias-aware [#13440](https://github.com/phalcon/cphalcon/issues/13440) [[doc]](https://docs.phalcon.io/5.13/di/)
- Added `Phalcon\Encryption\Security::setAutoRefresh(bool)` and `Phalcon\Encryption\Security::refreshToken()` to make CSRF-token rotation opt-out. When `setAutoRefresh(false)` is called, `getToken()` and `getTokenKey()` reuse the existing session values instead of generating a new pair on every call, eliminating the per-request session write that backend session stores billed per-write (DynamoDB, Redis-with-billing, etc.) would otherwise incur on read-only requests. `refreshToken()` provides an explicit, atomic rotation of both the token and the token key (used after login or any other state change where rotation is appropriate). The default `autoRefresh = true` preserves the existing one-time-use behavior; opt-in only [#14413](https://github.com/phalcon/cphalcon/issues/14413) [[doc]](https://docs.phalcon.io/5.13/encryption-security/)
- Added `Phalcon\Events\Manager::addSubscriber()`, `Phalcon\Events\Manager::removeSubscriber()`, `Phalcon\Events\Manager::getSubscribers()`, and `Phalcon\Events\Manager::clearSubscribers()` to register and unregister `Subscriber` instances. The subscriber's `getSubscribedEvents()` map is parsed once at attach time and each entry is routed through the regular listener pipeline; the result is cached per class name in a new internal `subscriberEventsCache` so repeated add/remove of the same subscriber class doesn't re-invoke the static method. `removeSubscriber()` is idempotent; `clearSubscribers()` iterates a snapshot of registered subscribers so the underlying property can be mutated safely during the walk. Subscribers are keyed by `spl_object_id()` so the same instance cannot be double-registered [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Added `Phalcon\Events\Manager::fireAll(string $eventType, object $source, mixed $data = null, bool $cancelable = true): array` returning every listener's return value as an indexed array. Independent of `collectResponses()`; the caller's collected state on `$this->responses` is stashed on entry and restored on exit so a `fireAll()` call from inside a `collect`-mode `fire()` does not pollute the outer accumulator [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Added `Phalcon\Events\Manager::halt()`, `Phalcon\Events\Manager::resume()`, and `Phalcon\Events\Manager::isHalted(): bool` - a manager-level kill switch separate from `$event->stop()`. `stop()` only stops the current dispatch chain; `halt()` survives across `fire()` calls and makes every subsequent `fire()` / `fireAll()` / `fireQueue()` call return immediately (`null` or `[]`) without dispatching, until `resume()` clears the flag. Use case: a security check or initialization-failure path that needs to abort all downstream event activity for the rest of the request [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Added `Phalcon\Events\Manager::setStopOnFalse(bool)` / `Phalcon\Events\Manager::isStopOnFalse(): bool` - an opt-in per-event short-circuit. When `setStopOnFalse(true)` has been called and the fire's `cancelable` flag is on, a listener returning literal `false` stops the dispatch loop for that event and pins the `fire()` return as `false`; later listeners cannot overwrite the cancel. Default off, preserving the pre-5.13 last-wins return-value semantics so existing codebases are unaffected. Independent of `halt()` and `$event->stop()` - only governs how the dispatch loop reacts to a literal `false` listener return [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Added `Phalcon\Events\Manager::setStrict(bool)` / `Phalcon\Events\Manager::isStrict(): bool` to enable strict mode. When strict mode is on, `fire()` and `fireAll()` throw `Phalcon\Events\Exception` on dispatch of an event with zero matching listeners - useful in development for catching typos in event names. Default off; opt-in only [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Added `Phalcon\Forms\Element\CheckGroup` and `Phalcon\Forms\Element\RadioGroup` form elements that render multiple `<input type="checkbox">` / `<input type="radio">` inputs from a single registered form-element entry. `CheckGroup` auto-suffixes the name with `[]` when not already present so PHP collects checked values into an array on submission; both elements bind cleanly because they live under a single key [#16996](https://github.com/phalcon/cphalcon/issues/16996) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Added `Phalcon\Forms\Form::load(Schema $schema, FormsLocator $locator)` that consumes element definitions from any `Schema` source and resolves their `type` strings through the supplied locator, allowing custom element types via `FormsLocator::setElement()` [#16996](https://github.com/phalcon/cphalcon/issues/16996) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Added `Phalcon\Forms\FormsLocator`, a closure-based registry for named forms (`get`/`has`/`set`) and element-type factories (`getElement`/`hasElement`/`setElement`); entity-less form lookups are lazily cached, while entity-aware lookups always rebuild. The default element registry seeds factories for `check`, `checkgroup`, `date`, `email`, `file`, `hidden`, `numeric`, `password`, `radio`, `radiogroup`, `select`, `submit`, `text`, and `textarea` [#16996](https://github.com/phalcon/cphalcon/issues/16996) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Added `Phalcon\Forms\Loader\ArrayLoader`, `Phalcon\Forms\Loader\JsonLoader`, and `Phalcon\Forms\Loader\YamlLoader` - three `Schema` implementations that feed `Form::load()` from a PHP array, a JSON string/file, or a YAML string/file (pecl/yaml) respectively [#16996](https://github.com/phalcon/cphalcon/issues/16996) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Added `Phalcon\Forms\Manager::loadForm(string $name, Schema $schema, ?object $entity = null)` and `Phalcon\Forms\Manager::getLocator()`; `loadForm()` builds a form from a `Schema`, registers it in the manager, and also registers a factory in the locator so subsequent entity-aware retrievals via `FormsLocator::get($name, $entity)` rebuild fresh instances. `Manager::__construct(FormsLocator $locator = null)` accepts a nullable locator and instantiates a default `FormsLocator` when omitted (BC: pre-existing `new Manager()` calls continue to work) [#16996](https://github.com/phalcon/cphalcon/issues/16996) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Added `Phalcon\Html\Escaper\AbstractEscaper`, `Phalcon\Html\Escaper\AttributeEscaper`, `Phalcon\Html\Escaper\CssEscaper`, `Phalcon\Html\Escaper\HtmlEscaper`, `Phalcon\Html\Escaper\JsEscaper`, and `Phalcon\Html\Escaper\UrlEscaper` as the per-context building blocks behind `Phalcon\Html\Escaper` [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-escaper/)
- Added `Phalcon\Html\Helper\Input\AbstractGroup`, `Phalcon\Html\Helper\Input\CheckboxGroup`, and `Phalcon\Html\Helper\Input\RadioGroup`; the base resolves option entries (scalar label or `[label, ...attrs]` map) into `<input>` + `<label>` pairs sharing a single HTML `name`, with auto-generated `id` per value (`{name}_{value}`) and per-item attribute pass-through. `CheckboxGroup` matches against an array (or scalar coerced into one) for `checked`; `RadioGroup` matches against a single scalar. Registered in `Phalcon\Html\TagFactory` as `inputCheckboxGroup` and `inputRadioGroup` [#16996](https://github.com/phalcon/cphalcon/issues/16996) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Added `Phalcon\Html\Helper\Input\Generic` and `Phalcon\Html\Helper\Input\AbstractChecked`; `Generic` accepts the HTML5 `type` via the constructor (with `setType()` to change it after construction), letting `TagFactory` register a single class for all type-string-only inputs and differentiate them through the recipe map [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Added `Phalcon\Html\Helper\Input\Select::placeholder(string $text)` to inject a `<option value="" disabled selected>$text</option>` first entry, and `Phalcon\Html\Helper\Input\Select::strict(bool $flag = true)` to opt the option/`selected` comparison from the new loose default into strict (`===`) matching [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Added `Phalcon\Html\Helper\Script::beginInternal()` and `endInternal(array $attributes = [], int $pos = -1)` to capture inline JavaScript via output buffering and append it to the asset stack as a `<script>...</script>` block [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Added `Phalcon\Html\Helper\Tag` (open tag) and `Phalcon\Html\Helper\VoidTag` (self-closing tag) as escape hatches for arbitrary tag names without a dedicated helper; available via `TagFactory` as `tag` and `voidTag` [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Added `Phalcon\Mvc\Router::getMethodRoutes(): array` - returns the internal HTTP-method index (routes bucketed by method string, unconstrained routes under `"*"`). `handle()` now builds a candidate list from only the matching-method bucket plus the unconstrained bucket and iterates that subset in reverse, eliminating the O(n) per-route HTTP-method check that previously ran against every registered route on each request [#17015](https://github.com/phalcon/cphalcon/issues/17015) [[doc]](https://docs.phalcon.io/5.13/routing/)
- Added `Phalcon\Mvc\Router::loadFromConfig(array|ConfigInterface $config): RouterInterface` - initializes the router from a data structure, accepting the top-level keys `defaultRoutes`, `removeExtraSlashes`, `defaults`, `notFound`, `routes`, and `groups`. Each `routes` entry supports `method` (one of `connect`, `delete`, `get`, `head`, `options`, `patch`, `post`, `purge`, `put`, `trace`; omitted = any method), `pattern`, `paths`, `name`, and `hostname`. Each `groups` entry supports `prefix`, `hostname`, `paths`, and a nested `routes` array, then is mounted via `mount()` [#15050](https://github.com/phalcon/cphalcon/issues/15050) [[doc]](https://docs.phalcon.io/5.13/routing/)
- Added `Phalcon\Mvc\Router\RouterFactory` with `load(array|ConfigInterface $config): RouterInterface` and `newInstance(bool $defaultRoutes = true): RouterInterface`, mirroring the idiomatic `ConfigFactory`/`LoggerFactory` pattern; `load()` honors the optional top-level `defaultRoutes` key (defaulting to `true`) and delegates route assembly to `Router::loadFromConfig()` [#15050](https://github.com/phalcon/cphalcon/issues/15050) [[doc]](https://docs.phalcon.io/5.13/routing/)
- Added `Phalcon\Paginator\Adapter\QueryBuilderCursor` - a keyset (cursor-based) pagination adapter that accepts a `QueryBuilder`, a `limit`, and a `cursorColumn` (the column used as the cursor key, typically a primary key). Each `paginate()` call fetches `limit + 1` rows using `cursorColumn > :cursor:` to skip already-seen rows; the extra row is used only to detect whether a next page exists and is never returned. `getNext()` returns the last visible row's cursor value, or `0` when no further page exists. `setCursor(int|null $cursor)` advances or resets the position. `getTotalItems()` and `getLast()` return `0` by design - no COUNT query is issued. Registered in `PaginatorFactory` as `"queryBuilderCursor"` [#14754](https://github.com/phalcon/cphalcon/issues/14754) [[doc]](https://docs.phalcon.io/5.13/db-pagination/)
- Added `Phalcon\Support\Collection:column(string $propertyOrMethod): array` (lift a single property/method off every item, keyed by the original collection key) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:each(callable $callback)` (run the callback for side effects and return `$this` for chaining) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:filter(callable $callback)` (new collection of items where the callback returns truthy) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:first()` / `last()` (head/tail value or `null` on empty) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:isEmpty(): bool`, `getType(): string|null` (the type guard configured at construction) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:keys(bool $insensitive = true)` and `values()` (the non-`get`-prefixed names, with `getKeys`/`getValues` retained as deprecated wrappers) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:map(callable $callback)` (new collection of transformed values, keys preserved) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:reduce(callable $callback, mixed $initial = null): mixed` (callback signature `($accumulator, $value, $key)`) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:replace(array $data): void` (clear then `init` in one call) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:sort(?callable $callback = null, int $order = SORT_ASC)` (`uasort` when a callback is supplied, otherwise `asort`/`arsort` keyed by `$order`) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Support\Collection:where(string $propertyOrMethod, mixed $value)` (strict-equality filter via `extractValue`) [#17000](https://github.com/phalcon/cphalcon/issues/17000) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Added `Phalcon\Time\Clock\Exception` with the `invalidModifier()` named constructor; `FrozenClock::adjust()` throws this exception uniformly across PHP versions when the modifier string cannot be parsed (catching `DateMalformedStringException` on PHP 8.3+ and trapping the `E_WARNING` plus `false` return on earlier versions, leaving the clock state untouched on failure) [#16965](https://github.com/phalcon/cphalcon/issues/16965) [[doc]](https://docs.phalcon.io/5.13/time-clock/)
- Added `Phalcon\Time\Clock` namespace with `ClockInterface`, `SystemClock`, and `FrozenClock` to wrap clock functionality for the application; `SystemClock` returns the current time as a `DateTimeImmutable` in a configurable timezone (with `fromUTC()` and `fromSystemTimezone()` named constructors), while `FrozenClock` returns a fixed instant for deterministic testing and exposes `set()` and `adjust()` to move the clock in place (returning `$this` for fluent chaining) [#16965](https://github.com/phalcon/cphalcon/issues/16965) [[doc]](https://docs.phalcon.io/5.13/time-clock/)
- Added `RETURNING` clause support to the Db dialect and adapter layers. New SQL-transformer method `Phalcon\Db\Dialect::returning(string $sqlQuery, array $columns): string` appends a `RETURNING` clause to an `INSERT` / `UPDATE` / `DELETE` statement; pass `["*"]` for `RETURNING *` or a list of column identifiers for `RETURNING "col1", "col2"`. `Phalcon\Db\Dialect\Postgresql::returning` and `Phalcon\Db\Dialect\Sqlite::returning` provide the emission (SQLite requires 3.35+). The base implementation in `Phalcon\Db\Dialect::returning` throws `Phalcon\Db\Exception`, which is inherited unchanged by `Phalcon\Db\Dialect\Mysql` since MySQL has no RETURNING construct. An empty `columns` array throws on PgSQL and SQLite. `Phalcon\Db\Adapter\AbstractAdapter::returning` provides the symmetric one-call passthrough so users can do `$connection->query($connection->returning($sql, ["id"]))`. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added `Raw` factory variants `aRaw`, `buttonRaw`, `elementRaw`, `labelRaw`, `olRaw`, and `ulRaw` to `Phalcon\Html\TagFactory`, each backed by a tuple recipe that pins `raw = true` on the constructor of the underlying helper [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Added an opt-in `bool $replaceArgs = false` fifth parameter to `Phalcon\Mvc\Url::get()`; when `true` and the supplied `$uri` already contains a query string, the existing query is parsed via `parse_str()` and merged under `array_merge($existing, (array) $args)` so user-supplied keys override colliding ones (e.g. `$url->get('http://example.com?page=1', ['page' => 5], null, null, true)` now returns `http://example.com?page=5` instead of `http://example.com?page=1&page=5`). The default (flag omitted) preserves the legacy append-with-`&` behavior to keep existing callers working [#16986](https://github.com/phalcon/cphalcon/issues/16986) [[doc]](https://docs.phalcon.io/5.13/mvc-url/)
- Added back the `generate_pecl` workflow job and `package.xml`, restoring publication of a `phalcon-pecl.tgz` artifact (also attached to GitHub releases) for a few more versions to give downstream users time to migrate to PIE
- Added functional/expression index support to `Phalcon\Db\Index`. Entries inside the `columns` array can now be `Phalcon\Db\RawValue` instances; string entries continue to be treated as column identifiers and escaped. The base `Phalcon\Db\Dialect::getIndexColumnList()` helper detects `RawValue` entries and per-dialect renders them - MySQL and PostgreSQL wrap each expression in its own parentheses (`KEY idx ((LOWER(col)))` and `CREATE INDEX ON t ((lower(col)))` respectively), while SQLite emits the expression verbatim (its grammar accepts `expr` directly as an `indexed-column`). The helper gains an optional `bool $wrapExpressions = true` flag - defaults are tuned per dialect at the call site so users do not need to think about it. Expressions compose with the descending direction (#8) and partial-index predicate (#9) without any additional API surface. Reverse-engineering of expressions is deferred (PostgreSQL via `pg_get_expr(pg_index.indexprs, ...)`, SQLite via `sqlite_master.sql` parsing - same conservative cutoff used in item #1). No new accessor method is needed - `Index::getColumns()` continues to return the entries (now of mixed string / `RawValue` type) [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added generated/computed column support to `Phalcon\Db\Column` via two new definition-array keys: `generated` (the SQL expression as a string; `null` keeps the column non-generated) and `generationStored` (`bool`, `false` → `VIRTUAL`, `true` → `STORED`; PostgreSQL ignores the flag and always emits `STORED`). Three new public methods report the state at runtime - `getGenerationExpression(): string | null`, `isGenerated(): bool`, `isGenerationStored(): bool`. The class enforces that a generated column cannot also declare `default` or `autoIncrement`. All three dialects (`Mysql`, `Postgresql`, `Sqlite`) emit `GENERATED ALWAYS AS (<expr>) VIRTUAL\|STORED` from `addColumn()`, `createTable()`, and (where supported) `modifyColumn()`, and skip the `DEFAULT` / `AUTO_INCREMENT` / `AUTOINCREMENT` clauses for generated columns. Reverse-engineering through `describeColumns()` is also wired up: MySQL switches from `SHOW FULL COLUMNS` to an equivalent `information_schema.COLUMNS` query that additionally returns `GENERATION_EXPRESSION`; PostgreSQL extends its `information_schema.columns` query with `is_generated` and `generation_expression`; SQLite switches from `PRAGMA table_info` to `PRAGMA table_xinfo` so the `hidden` flag (`2` → VIRTUAL, `3` → STORED) can populate `isGenerated()` / `isGenerationStored()`. SQLite cannot expose the generation expression through any pragma, so `getGenerationExpression()` round-trips as an empty string for SQLite-introspected generated columns (documented limitation). [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added hostname-aware URL generation to `Phalcon\Mvc\Url::get()`: when a named route carries a `setHostname()` restriction the returned URL is now protocol-relative (`//hostname/path`) so it works transparently under both HTTP and HTTPS [#17007](https://github.com/phalcon/cphalcon/issues/17007) [[doc]](https://docs.phalcon.io/5.13/mvc-url/)
- Added new Contracts (aka Interfaces) for `Phalcon\Encryption\Security` namely `Phalcon\Contracts\Encryption\Security\Security`, `CryptoUtils`, `CsrfProtection`, and `PasswordSecurity` and tied them to the component [#16991](https://github.com/phalcon/cphalcon/issues/16991) [[doc]](https://docs.phalcon.io/5.13/encryption-security/)
- Added partial-index support on `Phalcon\Db\Index` via a new `where` definition-array key (string). `Phalcon\Db\Index::getWhere(): string` exposes the configured predicate (empty string when none). `Phalcon\Db\Dialect\Postgresql::addIndex` and `Phalcon\Db\Dialect\Sqlite::addIndex` append `` WHERE <expr>`` to the emitted `CREATE INDEX` statement. MySQL has no partial-index feature and its dialect ignores the value. Reverse-engineering of the predicate is deferred for both PostgreSQL (requires `pg_get_expr(pg_index.indpred, pg_index.indrelid)`) and SQLite (requires `sqlite_master.sql` parsing) - same conservative cutoff used for SQLite generation expressions in item #1. Throws `Phalcon\Db\Exception` if the definition-array `where` key is supplied with a non-string value. [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added per-column sort direction (`ASC` / `DESC`) support on `Phalcon\Db\Index` via a new `directions` definition-array key. The array is parallel to `columns`; trailing positions absent from `directions` default to `ASC` at emission time. `Phalcon\Db\Index::getDirections(): array` exposes the configured list (empty array means "no per-column direction declared" - preserves the legacy plain `(col1, col2)` rendering exactly). A new protected `Phalcon\Db\Dialect::getIndexColumnList(IndexInterface)` helper centralizes the direction-aware emission and is now used by `Phalcon\Db\Dialect\Mysql::addIndex` / `createTable`, `Phalcon\Db\Dialect\Postgresql::addIndex` / `createTable`, and `Phalcon\Db\Dialect\Sqlite::addIndex` / `createTable`. `Phalcon\Db\Adapter\Pdo\Mysql::describeIndexes` reverse-engineers directions from the `Collation` column of `SHOW INDEXES` (`A` = ASC, `D` = DESC, NULL = ASC); the resulting `Index` only carries a non-empty `directions` array when at least one column is DESC, so existing introspection workflows that don't expect direction metadata see no diff. PostgreSQL and SQLite reverse-engineering of directions is deferred (`pg_index.indoption` and `sqlite_master.sql` parsing respectively - same conservative cutoff used for SQLite generation expressions in item #1). [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added per-option HTML attribute support to the `Phalcon\Html\Helper\Input\Select` data-provider path: `Phalcon\Html\Helper\Input\Select\SelectDataInterface` now also exposes `getAttributes()` returning `[optionValue => [attrName => stringValue, ...]]`; `ArrayData` accepts a second constructor argument with the resolved per-value attribute map, and `ResultsetData` accepts a third `attributesMap` argument (`htmlAttr => string|callable`) whose closures receive the current row. Resolution happens once in the providers (with `false`/`null` results dropped); rendering continues to flow through the existing `AbstractHelper` attribute pipeline [#16983](https://github.com/phalcon/cphalcon/issues/16983) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Added precompiled ARM64 binaries for Linux (`ubuntu-22.04-arm` runner) and macOS (`macos-14`, Apple Silicon) to the release artifacts. The `build_extension` CI matrix now produces `phalcon-php<ver>-<ts>-ubuntu-gcc-arm64.zip` and `phalcon-php<ver>-<ts>-macos-clang-arm64.zip` alongside the existing x64 builds, and the macOS composite action installs `pcre2`/`re2c` via Homebrew, points `CPPFLAGS`/`LDFLAGS` at the keg-only `pcre2` headers, and enables the extension in PHP's `PHP_CONFIG_FILE_SCAN_DIR` so `php --ri phalcon` succeeds [#16553](https://github.com/phalcon/cphalcon/issues/16553)
- Added spatial / geometry column-type support to `Phalcon\Db\Column` and the MySQL and PostgreSQL dialects. Eight new `Column::TYPE_*` constants land: `TYPE_GEOMETRY` (40), `TYPE_POINT` (41), `TYPE_LINESTRING` (42), `TYPE_POLYGON` (43), `TYPE_MULTIPOINT` (44), `TYPE_MULTILINESTRING` (45), `TYPE_MULTIPOLYGON` (46), `TYPE_GEOMETRYCOLLECTION` (47). `Phalcon\Db\Dialect\Mysql::getColumnDefinition` and `Phalcon\Db\Dialect\Postgresql::getColumnDefinition` emit the matching keywords (MySQL recognizes them natively from 5.7; PostgreSQL needs PostGIS installed, which interprets the keywords). SQLite has no native spatial type and its dialect leaves these constants in the `default` branch. `Phalcon\Db\Adapter\Pdo\Mysql::describeColumns` reverse-engineers the new types by `starts_with`-matching the column type - order in the switch was chosen so the longer multi-* / geometrycollection variants are matched before their shorter prefixes (`linestring` before `polygon`, etc.). PostgreSQL reverse-engineering for spatial types is deferred because `information_schema.data_type` does not consistently expose PostGIS type names without joining `pg_type` - users introspecting PostGIS schemas should query metadata directly until then. **Caveat - read-side WKB hydration is not part of this change.** A `POINT` selected directly with `SELECT location FROM items` still returns raw WKB bytes (cphalcon issues [#14769](https://github.com/phalcon/cphalcon/issues/14769) and [#13670](https://github.com/phalcon/cphalcon/issues/13670)); the workaround is to project `ST_AsText(location)` / `ST_AsBinary(location)` / `ST_AsGeoJSON(location)` server-side. The DDL/describe support shipped here is the schema-level prerequisite for any future result-set hydration helper [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added support for arbitrary SQL expressions in DDL `DEFAULT` clauses by recognizing `Phalcon\Db\RawValue` instances passed as `definition["default"]`. Previously each dialect quoted any non-numeric, non-`CURRENT_TIMESTAMP`, non-`NULL` default as a string literal - preventing legitimate expression defaults like MySQL 8.0.13+ `DEFAULT (UUID())`, PostgreSQL `DEFAULT gen_random_uuid()` / `DEFAULT nextval('seq')`, or SQLite 3.31+ `DEFAULT strftime('%s','now')`. `Phalcon\Db\Dialect\Mysql::addColumn` / `createTable` / `modifyColumn`, `Phalcon\Db\Dialect\Postgresql::castDefault` (used by all three of its DDL paths), and `Phalcon\Db\Dialect\Sqlite::addColumn` / `createTable` now detect `RawValue` defaults and emit `DEFAULT <raw>` verbatim. Plain-scalar and `CURRENT_TIMESTAMP` / `NULL` keyword defaults continue to take the existing whitelist path unchanged. `Column::hasDefault()` already treats a `RawValue` as a non-null default, so `isAutoIncrement()` semantics and the generated-column "no default allowed" guard from item #1 remain correct [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)
- Added the canonical Events contracts under the `Phalcon\Contracts\Events` namespace: `Phalcon\Contracts\Events\Event` (replaces `Phalcon\Events\EventInterface`), `Phalcon\Contracts\Events\EventsAware` (replaces `Phalcon\Events\EventsAwareInterface`), and `Phalcon\Contracts\Events\Manager` (replaces `Phalcon\Events\ManagerInterface`). The legacy `Phalcon\Events\*Interface` types are kept as thin extensions of their canonical counterparts, marked `@deprecated`, matching the migration pattern used for `Phalcon\Support\Collection\CollectionInterface`. Existing implementors and typehints continue to work in the v5 line [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Enabled `dropColumn()` on the SQLite dialect to emit `ALTER TABLE ... DROP COLUMN ...` instead of throwing unconditionally - SQLite 3.35+ natively supports `ALTER TABLE DROP COLUMN`, and pre-empting it at the cphalcon dialect level prevented modern users from using the feature. On engines older than 3.35 the server itself rejects the statement at execution time, with a clearer "near 'DROP': syntax error" message than the previous cphalcon-side throw. `Phalcon\Db\Adapter\Pdo\Sqlite::dropColumn` already passes through the dialect output via `AbstractAdapter`, so users now get a one-call `$connection->dropColumn(...)` on SQLite [#14719](https://github.com/phalcon/cphalcon/issues/14719) [[doc]](https://docs.phalcon.io/5.13/db-layer/)

### Fixed

- Fixed `.ci/release-notes.sh` failing intermittently with `grep: write error: Broken pipe` once `CHANGELOG-5.0.md` grew past the pipe-buffer threshold; the `grep ... | head -n N` pipelines (with `set -o pipefail` active) gave `grep` `SIGPIPE` when `head` closed the FD before `grep` finished writing, aborting the step. Replaced both pipelines with single-pass `awk` programs that exit on the matching line, eliminating the broken-pipe race entirely while keeping the original "extract from the latest `## ` heading down to one line above the next `# ` heading" behavior
- Fixed `Phalcon\Annotations\AnnotationsFactory::newInstance("memory")` segfaulting the PHP process [#16962](https://github.com/phalcon/cphalcon/issues/16962) [[doc]](https://docs.phalcon.io/5.13/annotations/)
- Fixed `Phalcon\Cli\Console::handle()` and `Phalcon\Mvc\Application::handle()` not propagating the configured `defaultModule` to the dispatcher when the router returned no module name; both methods resolved a local `moduleName` (router value, falling back to `$this->defaultModule`) for the module-loading path but then called `$dispatcher->setModuleName($router->getModuleName())`, discarding the fallback. As a result, dispatcher events such as `dispatch:beforeDispatchLoop` saw an empty module name even when `setDefaultModule()` had been called. Both call sites now pass the resolved `moduleName` to the dispatcher [#17013](https://github.com/phalcon/cphalcon/issues/17013) [[doc]](https://docs.phalcon.io/5.13/application-cli/)
- Fixed `Phalcon\Db\Dialect::getSqlExpression()` not propagating the outer `bindCounts` map into the recursive `select()` call rendered for nested sub-SELECTs, so an array placeholder (`{name:array}`) inside a sub-SELECT kept the parse-time `times` count baked into the cached PHQL intermediate; a second execution of the same outer PHQL with the same bind name but a different bind-array size fell back to the stale count and PDO raised "Invalid parameter number: number of bound variables does not match number of tokens" (workaround was `Phalcon\Mvc\Model\Query::clean()` between calls). The `select` branch now seeds the nested definition's `bindCounts` from the outer `bindCounts` on a local copy of the nested intermediate so the runtime placeholder-count override fires at every nesting level without mutating the cached intermediate [#17004](https://github.com/phalcon/cphalcon/issues/17004) [[doc]](https://docs.phalcon.io/5.13/db-phql/)
- Fixed `Phalcon\Events\Manager::fireQueue()` segfaulting when invoking a `Closure` listener whose declared parameter count is less than the three arguments the manager passes (`$event, $source, $data`). The previous `handler->__invoke(...)` form routed through PHP's strict C-level method-call path which sets up a 3-slot frame that the closure's own 2-slot (or fewer) frame cannot accept, producing undefined behavior and a process crash. The dispatch path now uses Zephir's `{handler}(args)` callable-invocation syntax, which routes through `zend_call_function` and handles closure arity mismatch correctly [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)
- Fixed `Phalcon\Forms\Form::bind()` leaving the entity property untouched when a `Check` element's key was absent from submitted data (the browser behavior for an unchecked checkbox), so a previously-`true` field could never be reset by re-submitting the form. `Phalcon\Forms\Element\Check` gains opt-in `setUncheckedValue()` / `getUncheckedValue()` / `hasUncheckedValue()`; when an unchecked value has been registered and the element's data key is missing from the bind payload, `bind()` injects the registered value into `$data` before the main loop so it flows through whitelist, filters, and entity setters identically to a submitted value. Checks without an explicit `setUncheckedValue()` preserve the previous behavior (entity untouched) [#16982](https://github.com/phalcon/cphalcon/issues/16982) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Fixed `Phalcon\Forms\Form::bind()` silently dropping data for `Radio` elements registered under distinct form-element identifiers but sharing the same HTML `name` attribute (e.g. `new Radio('r0', ['name' => 'banned'])`, `new Radio('r1', ['name' => 'banned'])` bound from `['banned' => 'yes']`); the bind loop only looked up `$this->elements[$key]` and skipped the value when no element matched, so the entity property was never set. The lookup now falls back to scanning registered elements for one whose HTML `name` attribute matches the data key [#15957](https://github.com/phalcon/cphalcon/issues/15957) [[doc]](https://docs.phalcon.io/5.13/forms/)
- Fixed `Phalcon\Html\Helper\Input\AbstractInput::__invoke()` always assigning an `id` attribute equal to the element name, producing invalid markup like `id="tags[]"` when the name contained brackets (array names or indexed names like `roles[0]`). The auto-`id` is now skipped when the name contains `[`, matching the behavior of the corresponding helper in the PHP-side `phalcon/phalcon` library
- Fixed `Phalcon\Mvc\Model::__set()` not clearing the cached related record when a `belongsTo` relation alias is assigned `null`; calling a getter before setting the relation to null caused `preSaveRelatedRecords()` to overwrite the FK back to its previous value on save [#16611](https://github.com/phalcon/cphalcon/issues/16611) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Fixed `Phalcon\Mvc\Model::assign()` and `Phalcon\Mvc\Model::writeAttribute()` silently dropping values for columns whose name maps to a reserved internal setter (`source`, `schema`, `dirtyState`, `connectionService`, `readConnectionService`, `writeConnectionService`, `eventsManager`, `transaction`, `snapshotData`, `oldSnapshotData`); `Phalcon\Mvc\Model::possibleSetter()` returned `true` for these reserved names while skipping both the setter call and the property assignment, so the value never landed on the model. It now returns `false` for reserved setters, letting the caller fall through to direct property assignment while still preventing the reserved internal setter from being hijacked by the column [#16617](https://github.com/phalcon/cphalcon/issues/16617) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Fixed `Phalcon\Mvc\Model::cloneResultMap()` and `Phalcon\Mvc\Model::possibleSetter()` throwing `TypeError` during hydration when a model setter has a strict type hint (e.g. `?array`) and the raw database value is incompatible; the ORM now catches `TypeError` and falls back to direct property assignment [#16956](https://github.com/phalcon/cphalcon/issues/16956) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Fixed `Phalcon\Mvc\Model::doLowInsert()` throwing `Unable to insert into <table> without data` when saving a model whose only column is an auto-increment primary key; on dialects where `useExplicitIdValue()` is `false` (MySQL, SQLite) the identity branch produced an empty `values` array. The identity column is now added with the connection's default identity value when the resulting `values` array would otherwise be empty [#156](https://github.com/phalcon/phalcon/issues/156) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Fixed `Phalcon\Mvc\Model::find()` return type regression introduced in 5.7.0 (PR [#16578](https://github.com/phalcon/cphalcon/pull/16578)) that removed the `: ResultsetInterface` runtime declaration while adding `@template T` generic docblocks for IDE autocompletion. Subclass overrides that narrowed the return type to `: ResultsetInterface` and call sites that assigned the result to a typed property (`private ResultsetInterface $robots = Robots::find(...)`) broke on upgrade from 5.6.x. The runtime return type is restored, and the docblock claim was tightened from `T[]|\Phalcon\Mvc\Model\Resultset<int, T>` to `\Phalcon\Mvc\Model\Resultset<int, T>` so it is a strict subtype of the runtime declaration - Psalm previously raised `MismatchingDocblockReturnType` on the generated ide-stubs because `T[]` resolves to `array<int, T>`, which is not a `ResultsetInterface` (the `T[]` half of the union was always false; `find()` never returns a plain PHP array, even under `HYDRATE_ARRAYS`). Modern IDEs and static analyzers continue to resolve element-type `T` through the surviving generic annotation. `findFirst()` is unchanged - it has always returned `var | null` because it can legitimately return a `ModelInterface`, a `Row`, or `null` depending on the projection [#16633](https://github.com/phalcon/cphalcon/issues/16633) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Fixed `Phalcon\Mvc\Model::findFirst()` docblock claim that diluted the generic return type. The annotation was `@return T|\Phalcon\Mvc\ModelInterface|\Phalcon\Mvc\Model\Row|null` (introduced by PR [#16578](https://github.com/phalcon/cphalcon/pull/16578)), but since `@template T of static` and every `Model` subclass implements `ModelInterface`, the `ModelInterface` union member is a supertype of `T` and Psalm/PHPStan collapsed the union to its widest member - losing `T` and forcing user code that called child-class-specific methods on `findFirst()`'s result to assert `instanceof self` first. The redundant `ModelInterface` member is removed; the annotation is now `@return T|\Phalcon\Mvc\Model\Row|null`, preserving `T` for static analyzers while keeping `Row` (returned when the query projects scalar columns) and `null` (no match) honest [#16661](https://github.com/phalcon/cphalcon/issues/16661) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Fixed `Phalcon\Mvc\Model::preSaveRelatedRecords()` skipping `doSave()` for `belongsTo` parents whose `dirtyState` was `DIRTY_STATE_PERSISTENT`; modifications made to an existing parent (loaded via `findFirst()`) and then attached to a new child were silently dropped on save, while only the FK was written. The dirtyState short-circuit (originally added to break circular hasMany ⇄ belongsTo recursion) is removed and recursion is now prevented by the `visited` collection already enforced inside `doSave()` [#16222](https://github.com/phalcon/cphalcon/issues/16222) [[doc]](https://docs.phalcon.io/5.13/db-models/)
- Fixed `Phalcon\Mvc\Model\Query::executeUpdate()` and `Phalcon\Mvc\Model::doLowUpdate()` for PHQL `UPDATE ... SET <expr>` expressions with placeholders (e.g. `col = col + :inc:`): named placeholders embedded in expression SQL are now resolved before creating `RawValue` to avoid PDO "mixed named and positional parameters", and dynamic-update comparisons now always treat `RawValue` assignments as changed so updates are not skipped when the current numeric value is `0` [#16976](https://github.com/phalcon/cphalcon/issues/16976) [[doc]](https://docs.phalcon.io/5.13/db-phql/)
- Fixed `Phalcon\Mvc\Model\Query::parse()` returning a cached intermediate representation with the model's original schema/source baked into the `tables` slot, so subsequent `find()`/`findFirst()`/`findBy*()` calls for the same PHQL string kept emitting SQL pointing at the original schema after the model changed its schema at runtime (e.g. via a wrapper calling `setSchema()`); the cache-hit branch now refreshes the `tables` entries from the live `model->getSchema()`/`getSource()` before returning, preserving the parse-cache benefit while keeping the rendered FROM clause in sync with current model state [#17020](https://github.com/phalcon/cphalcon/issues/17020) [[doc]](https://docs.phalcon.io/5.13/db-phql/)
- Fixed `Phalcon\Mvc\Router::handle()` leaving unmatched optional named parameters (e.g. `{id:(/[0-9]+)?}{slug:(/.+)?}`) in the resulting params with their raw regex group positions (`id => 1, slug => 2`) instead of unsetting them; the unset branch was nested under `else` of `typeof converters === "array"`, but `Route::$converters` defaults to `[]` so the branch was unreachable. The check is now flattened so an unmatched positional parameter is removed when no per-part converter exists [#16559](https://github.com/phalcon/cphalcon/issues/16559) [[doc]](https://docs.phalcon.io/5.13/routing/)
- Fixed `Phalcon\Mvc\View::getActiveRenderPath()` returning only the first candidate path as a string when a single `viewsDir` was configured with multiple registered render engines and the view was not found; the method now collapses the internal `activeRenderPaths` array to a string only when it contains exactly one element, returning the full array of candidate paths in all other cases [#16614](https://github.com/phalcon/cphalcon/issues/16614) [[doc]](https://docs.phalcon.io/5.13/views/)
- Fixed `Phalcon\Mvc\View\Engine\Volt\Compiler` rejecting single-quoted Volt string literals containing an escaped single quote (e.g. `{{ 'Let\'s Encrypt' }}`) with a downstream PHP `T_STRING` parse error; the Volt scanner already preserves the user-written `\'` verbatim in `expr["value"]`, so the compiler's `str_replace("'", "\\'", expr["value"])` step double-escaped it into `Let\\'s Encrypt`, producing PHP source `'Let\\'s Encrypt'` that PHP read as `Let\` followed by an unexpected identifier. Dropped the redundant `str_replace`; the scanner's regex (`(['] ([\\][']|[\\].|...) ['])`) guarantees `expr["value"]` is already valid PHP single-quoted string content [#17002](https://github.com/phalcon/cphalcon/issues/17002) [[doc]](https://docs.phalcon.io/5.13/volt/)
- Fixed `Phalcon\Support\Collection::get()` returning the wrapping object's mangled property table when called with `$cast = 'array'` on a stored object (e.g. a nested `Phalcon\Config\Config`, which `Config::setData()` wraps around every array value). The previous implementation called `settype($value, 'array')` unconditionally, which on an object exposes PHP's null-prefixed internal protected-property names (`"\0*\0data"`, `"\0*\0lowerKeys"`, etc.) instead of the intended array form, so `$config->get('outKey', [], 'array')['inKey']` raised `Notice: Undefined index`. The cast branch now special-cases `'array'` for objects exposing a `toArray()` method and delegates to that method; scalar casts (`int`, `bool`, `float`, `string`, `null`, `object`) and `'array'` on a plain `stdClass` (no `toArray()`) are unchanged [#17005](https://github.com/phalcon/cphalcon/issues/17005) [[doc]](https://docs.phalcon.io/5.13/support-collection/)
- Fixed `Phalcon\Tag\Select::optionsFromArray()` not escaping option label text, allowing XSS injection via malicious values; labels are now escaped with `escapeHtml()` and option values with `escapeHtmlAttr()` via the escaper service, consistent with `optionsFromResultset()` [#16660](https://github.com/phalcon/cphalcon/issues/16660) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
- Fixed nested `Phalcon\Events\Manager::fire()` calls clobbering the outer caller's collected `$this->responses` state. Previously the inner fire would reset `$this->responses = []` and append its own listener returns there, so after the nested fire returned the outer caller's `getResponses()` saw the inner fire's results instead of its own. `fire()` now tracks re-entrancy depth via a new internal `fireDepth` counter and, on a nested call with `collect=true`, stashes the outer's responses on entry and restores them on exit so the outer accumulator is preserved across the inner dispatch. `fireAll()` applies the same stash-and-restore pattern unconditionally so it never pollutes outer collected state regardless of the manager's `collectResponses()` setting [#17006](https://github.com/phalcon/cphalcon/issues/17006) [[doc]](https://docs.phalcon.io/5.13/events/)

### Removed

- Removed calls to `version_compare` that led to pre PHP 8.0 code [#16966](https://github.com/phalcon/cphalcon/issues/16966)
- Removed the per-type input helpers `Phalcon\Html\Helper\Input\Color`, `Date`, `DateTime`, `DateTimeLocal`, `Email`, `File`, `Hidden`, `Image`, `Input`, `Month`, `Numeric`, `Password`, `Range`, `Search`, `Submit`, `Tel`, `Text`, `Time`, `Url`, and `Week`; their `TagFactory` service names (`inputColor`, `inputDate`, ...) now resolve to `Phalcon\Html\Helper\Input\Generic` with the appropriate `type` baked into the recipe, so callers using the factory keep working unchanged [#16971](https://github.com/phalcon/cphalcon/issues/16971) [[doc]](https://docs.phalcon.io/5.13/html-tagfactory/)
