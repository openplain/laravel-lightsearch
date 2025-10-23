# Changelog

All notable changes to `laravel-lightsearch` will be documented in this file.

## v1.0.0 - 2025-10-23

### Added
- Initial release of Laravel LightSearch for Laravel Scout
- **Automatic fuzzy search** on PostgreSQL with pg_trgm extension
- Database-specific search optimizations for MySQL, PostgreSQL, and SQLite
- Configurable field weights for search relevance tuning
- Stopword filtering to reduce index size
- Minimum token length configuration
- Support for string and UUID primary keys
- Proper pagination implementation with offset/limit
- Comprehensive documentation and examples
- PHPUnit test suite structure
- GitHub Actions CI/CD workflow
- Laravel Pint code formatting

### Fixed
- Critical bug: Search now properly filters by model class
- `flush()` method now only deletes specific model instead of truncating entire table
- Proper support for non-integer primary keys (UUIDs, strings)
- Correct pagination offset calculations

### Changed
- Migrated from `ktr/laravel-lightsearch-driver` to `openplain/laravel-lightsearch`
- Improved migration with composite indexes for better performance
- Enhanced configuration with dedicated config file
- Database engine selection now automatic based on connection driver
- Field weighting implemented via duplicate token entries (no unique constraint)

### Technical Highlights
- Average search time: 2.95ms on 26,191 records
- Fuzzy search: ~110ms with PostgreSQL pg_trgm
- Consistent result ordering with secondary sort by record_id
- Full Unicode support tested with Faroese addresses (ø, á, ð, í, ú, ý)
