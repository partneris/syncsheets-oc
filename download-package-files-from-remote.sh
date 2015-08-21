#!/bin/bash

xargs -i wget -r -x -nH --cut-dirs=1 'ftp://gss15:t2de52mig2@gss15.opencartcart.com/public_html/{}' <<EOF
admin/controller/feed/syncsheets.php
admin/language/english/feed/syncsheets.php
admin/model/feed/hooks/common_hooks.php
admin/model/feed/syncsheets.php
admin/syncsheets_feed.php
admin/view/template/feed/syncsheets_account.tpl
admin/view/template/feed/syncsheets_settings.tpl
admin/view/template/feed/syncsheets_fields_20.tpl
admin/view/template/feed/syncsheets_settings_20.tpl
admin/view/template/feed/syncsheets_fields_g.tpl
admin/view/template/feed/syncsheets_fields.tpl
EOF
