# grab translatable strings from sources,
# and merge them into messages.pot
xgettext --default-domain=argo --language=PHP --output=messages.pot ../*.php ../lib/*.php ../lib/Module/*.php ../lib/Module/Course/*.php ../lib/HTML/QuickForm/Renderer/*.php

# merge the new strings from messages.pot into locale-specific po-s.
msgmerge et_EE/LC_MESSAGES/VIKO.po messages.pot > new.po
mv new.po et_EE/LC_MESSAGES/VIKO.po

# msgmerge ru_RU/LC_MESSAGES/VIKO.po messages.pot > new.po
# mv new.po ru_RU/LC_MESSAGES/VIKO.po


