# Makefile for the translatr TYPO3 extension
# Runs in the ddev web container: "ddev make <target>"

.DEFAULT_GOAL := help

.PHONY: help
help: ## Show available targets
	@awk 'BEGIN{FS=":.*##";print "\nUsage: ddev make <target>\n"} /^[a-zA-Z0-9_.-]+:.*##/ {printf "  %-12s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.Build/vendor: composer.json
	composer install --no-progress -n
	@touch $@

.PHONY: install
install: .Build/vendor ## Install composer dependencies

# ===================================
# Code Quality
# ===================================

.PHONY: ci
ci: .Build/vendor ## Run all code checks (PHP-CS-Fixer, Rector, PHPStan)
	composer run ci

.PHONY: lint
lint: ci ## Alias of "ci"

.PHONY: cgl
cgl: .Build/vendor ## Check the code style (PHP-CS-Fixer, dry run)
	composer run ci:php:cs-fixer

.PHONY: rector
rector: .Build/vendor ## Check for Rector changes (dry run)
	composer run ci:php:rector

.PHONY: typecheck
typecheck: .Build/vendor ## Run PHPStan static analysis
	composer run ci:php:stan

.PHONY: fix
fix: .Build/vendor ## Apply Rector and PHP-CS-Fixer fixes
	composer run fix

.PHONY: format
format: fix ## Alias of "fix"

.PHONY: clean
clean: ## Remove caches of the code quality tools
	rm -rf .Build/.cache
