# Tokkie

An over simplified and horribly written token dispenser, primarily intended for Docker

# Setup

```bash
composer install --no-dev
${EDITOR:-nano} ./config/tokkie.php
php ./bin/generate-keys.php
# Edit output command, to your preferred setup.
php ./bin/docker-start-command.php
# Run: docker create command provided by docker-start-command.php
```

---

"This isn't enough docs, how dare you to publish this half ready shit!!". yeah #TODO