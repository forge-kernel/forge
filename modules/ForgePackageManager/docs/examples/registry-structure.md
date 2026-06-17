# Registry Structure

All registries must follow this directory structure regardless of source type.

## Directory Layout

```
registry-root/
├── modules.json
└── modules/
    └── module-name/
        └── version/
            └── module-name-version.zip
```

## modules.json Format

The `modules.json` file at the root contains metadata for all modules:

```json
{
  "module-name": {
    "latest": "1.0.0",
    "versions": {
      "1.0.0": {
        "description": "Module description",
        "url": "module-name/1.0.0",
        "integrity": "sha256-hash-here"
      },
      "0.9.0": {
        "description": "Previous version",
        "url": "module-name/0.9.0",
        "integrity": "sha256-hash-here"
      }
    }
  },
  "another-module": {
    "latest": "2.0.0",
    "versions": {
      "2.0.0": {
        "description": "Another module",
        "url": "another-module/2.0.0",
        "integrity": "sha256-hash-here"
      }
    }
  }
}
```

## Field Descriptions

### Top Level

- **module-name** (string) - Unique identifier for the module

### Module Object

- **latest** (string) - Latest version identifier
- **versions** (object) - Object containing all available versions

### Version Object

- **description** (string) - Human-readable description
- **url** (string) - Relative path to module directory (without .zip extension)
- **integrity** (string) - SHA256 hash of the zip file

## Module Directory Structure

Each module version directory should contain:

```
modules/module-name/version/
└── module-name-version.zip
```

The zip file should contain the complete module structure:

```
module-name-version.zip
└── module-name/
    ├── forge.json
    ├── src/
    └── ...
```

## Integrity Hash

The integrity hash is a SHA256 hash of the zip file:

```bash
sha256sum modules/module-name/1.0.0/module-name-1.0.0.zip
```

## Example

Complete example structure:

```
registry/
├── modules.json
└── modules/
    ├── my-module/
    │   ├── 1.0.0/
    │   │   └── my-module-1.0.0.zip
    │   └── 0.9.0/
    │       └── my-module-0.9.0.zip
    └── another-module/
        └── 2.0.0/
            └── another-module-2.0.0.zip
```

With `modules.json`:

```json
{
  "my-module": {
    "latest": "1.0.0",
    "versions": {
      "1.0.0": {
        "description": "My awesome module",
        "url": "my-module/1.0.0",
        "integrity": "abc123def456..."
      },
      "0.9.0": {
        "description": "Previous version",
        "url": "my-module/0.9.0",
        "integrity": "def456ghi789..."
      }
    }
  },
  "another-module": {
    "latest": "2.0.0",
    "versions": {
      "2.0.0": {
        "description": "Another great module",
        "url": "another-module/2.0.0",
        "integrity": "ghi789jkl012..."
      }
    }
  }
}
```

