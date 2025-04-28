# Contributing to WordForge

Thank you for considering contributing to WordForge! This document outlines the process for contributing to the project and how to set up your development environment.

## Development Environment

### Requirements
- PHP 8.1 or higher
- Composer

### Setup
1. Clone the repository
   ```bash
   git clone https://github.com/codemystify/wordforge.git
   cd wordforge
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Run tests
   ```bash
   composer test
   ```

## Testing

WordForge uses PHPUnit for testing. The test suite is organized into the following categories:

- **Unit Tests**: Tests individual components in isolation
- **Integration Tests**: Tests components working together
- **Feature Tests**: Tests complete features from end to end

You can run specific test suites using the following commands:

```bash
composer test-unit
composer test-integration
composer test-feature
```

To generate a coverage report, run:

```bash
composer test-coverage
```

## Code Quality

WordForge uses various tools to ensure code quality:

### PHP CodeSniffer

We follow PSR-12 coding standards. You can check your code for compliance using:

```bash
composer cs
```

To automatically fix coding standard issues:

```bash
composer cs-fix
```

### PHPStan

Static analysis is performed using PHPStan at level 5. Check your code with:

```bash
composer analyze
```

### All Checks

Run all code quality checks at once:

```bash
composer check
```

## Continuous Integration

The project uses GitHub Actions for CI. Every pull request and push to main branches triggers the following:

- Tests on PHP 8.1, 8.2, 8.3, and 8.4
- Code quality checks (PHPStan and PHP_CodeSniffer)

### CI Pipeline Structure

1. **Test Job**:
   - Runs on multiple PHP versions (8.1 to 8.4)
   - Installs dependencies
   - Runs the test suite

2. **Code Quality Job**:
   - Runs on PHP 8.3
   - Performs coding standards checks
   - Performs static analysis

## Submitting Changes

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-new-feature`
3. Make your changes
4. Run tests and ensure they pass: `composer test`
5. Run code quality checks: `composer check`
6. Commit your changes: `git commit -am 'Add some feature'`
7. Push to the branch: `git push origin feature/my-new-feature`
8. Submit a pull request

## Pull Request Guidelines

- Keep pull requests focused on a single change
- Write clear, descriptive commit messages
- Include tests for new features
- Update documentation if necessary
- Ensure all tests pass and code quality checks succeed
