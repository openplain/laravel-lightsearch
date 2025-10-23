# Contributing to LightSearch

Thank you for considering contributing to LightSearch! We welcome contributions from the community.

## How to Contribute

### Reporting Bugs

If you find a bug, please open an issue on GitHub with:

- A clear title and description
- Steps to reproduce the issue
- Expected vs actual behavior
- Your environment (PHP version, Laravel version, database driver)
- Any relevant code snippets or error messages

### Suggesting Features

We love new ideas! Please open an issue with:

- A clear description of the feature
- Use cases and examples
- Why this feature would benefit other users

### Pull Requests

1. **Fork the repository** and create your branch from `master`

```bash
git checkout -b feature/my-new-feature
```

2. **Install dependencies**

```bash
composer install
```

3. **Make your changes**
   - Write clean, readable code
   - Follow PSR-12 coding standards
   - Add tests for new features
   - Update documentation if needed

4. **Run tests**

```bash
composer test
```

5. **Format your code**

```bash
composer format
```

6. **Commit your changes**

Use clear, descriptive commit messages:

```bash
git commit -m "Add: Support for custom tokenizers"
git commit -m "Fix: Pagination offset calculation"
git commit -m "Docs: Update configuration examples"
```

7. **Push to your fork**

```bash
git push origin feature/my-new-feature
```

8. **Open a Pull Request**

Include in your PR description:
- What changes you made
- Why you made them
- How to test the changes
- Screenshots (if applicable)

## Development Guidelines

### Code Style

We follow PSR-12 coding standards. Run Laravel Pint to format your code:

```bash
composer format
```

### Testing

All new features must include tests. Run the test suite:

```bash
composer test
```

### Documentation

- Update README.md if you add new features
- Add inline comments for complex logic
- Update config file comments if you add new options

## Code of Conduct

### Our Standards

- Be respectful and inclusive
- Welcome constructive feedback
- Focus on what's best for the community
- Show empathy towards others

### Unacceptable Behavior

- Harassment or discriminatory language
- Trolling or insulting comments
- Personal or political attacks
- Publishing others' private information

## Questions?

Feel free to open an issue for questions or join discussions on:

- GitHub Discussions: [github.com/openplain/lightsearch/discussions](https://github.com/openplain/lightsearch/discussions)

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Recognition

Contributors will be recognized in:
- The project README
- Release notes
- Our appreciation! 🎉

Thank you for making LightSearch better!
