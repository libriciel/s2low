#! /bin/bash

date=$(date +%d/%m/%Y)



cat <<EOF

BUILD_DATE=$date
BUILD_ID=${CI_BUILD_ID}
VERSION=${CI_COMMIT_REF_NAME}

EOF
