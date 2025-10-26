define php_run
	cd ~/Dev/docker-lamp/;  docker compose exec php sh -c "cd wp-kama.dev; $1"
endef

php.connect:
	$(call php_run, cd public_html/wp-content/plugins/kama-clic-counter; bash)

phpunit:
	$(call php_run, cd public_html/wp-content/plugins/kama-clic-counter; composer run phpunit)
phpunit_xdebug:
	$(call php_run, cd public_html/wp-content/plugins/kama-clic-counter; composer run phpunit_xdebug)


#########################################################
#                       i18n                            #
#########################################################

LANGUAGES_DIR := public_html/wp-content/plugins/kama-clic-counter/languages

i18n_update_po:
	bash languages/make-pot.sh
	$(call php_run, wp i18n update-po  "$(LANGUAGES_DIR)/aa_AA.pot")

i18n_make_mo_php:
	$(call php_run, wp i18n make-mo   "$(LANGUAGES_DIR)"  "$(LANGUAGES_DIR)/build")
	$(call php_run, wp i18n make-php  "$(LANGUAGES_DIR)"  "$(LANGUAGES_DIR)/build")
