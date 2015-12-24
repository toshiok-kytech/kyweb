rm -fr docs
mkdir docs
phpdoc --hidden --title 'KYWeb Framework Documentation' --directory private/library --target docs --ignore 'library/example/*.*'