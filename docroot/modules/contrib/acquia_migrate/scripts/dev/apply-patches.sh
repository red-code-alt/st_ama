#!/bin/bash
set -e

main () {
  RECS="$2"
  PACKAGE=$1

  local patch_list=(`jq -r ".data | map(select((.package != null) and (.package == \"$PACKAGE\"))) | first | .patches | to_entries | .[].value" < $RECS`)

  for patch in "${patch_list[@]}"
  do
    echo "Applying $patch …"
    if [[ $patch == https* ]]
    then
      # Remote patch.
      curl -Ls $patch | patch -p1;
    else
      # Local patch.
      patch -p1 < $module_dir/$patch;
    fi
    if [[ $PACKAGE == drupal/core ]]
    then
      git add core
    else
      git add -A
    fi
    git reset -- $patch && git commit -n -q -m "AMA PATCH $patch"
    echo "\n"
  done < /dev/stdin
}
main $@
