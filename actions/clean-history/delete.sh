#!/bin/sh

# Init default values
older=30
status="failure,cancelled"
repo=$(git config --get remote.origin.url | sed -E 's/.*[:\/]([^\/]+\/[^\.]+)(\.git)?$/\1/')

# handle arguments with getopt
OPTSTRING="w:o:s:r:"
while getopts ${OPTSTRING} opt; do
    case ${opt} in
    w)
        workflows=$(echo ${OPTARG} | tr ',' ' ')
        echo "Selected workflow: ${workflows}" >&2
        ;;
    o)
        older=${OPTARG}
        echo "Select older than: ${older} days" >&2
        ;;
    s)
        status=${OPTARG}
        echo "Select status: ${status}" >&2
        ;;
    r)
        repo=${OPTARG}
        echo "Select repository: ${repo}" >&2
        ;;
    :)
        echo "Option -${OPTARG} requires an argument." >&2
        exit 1
        ;;
    ?)
        echo "Invalid option: -${OPTARG}." >&2
        exit 1
        ;;
    esac
done

if [ -z "$workflows" ]; then
    echo "No workflow specified. Listing all workflow runs for $repo..." >&2

    # Creates a temporary file to store workflow data.
    workflows_temp=$(mktemp)

    # Lookup workflow
    gh api repos/$repo/actions/workflows | jq -r '.workflows[] | [.id, .path] | @tsv' >$workflows_temp
    cat "$workflows_temp"

    # Get the list of workflow names that are not successful or failed
    workflows_names=$(awk '{print $2}' $workflows_temp | grep -v "main")

    # Save a comma separated list of workflow names using basename
    workflows=$(echo "$workflows_names" | xargs -I{} basename {} | tr '\n' ' ')
fi

if [ -z "$workflows" ]; then
    echo "Nothing to remove" >&2
else
    echo "Removing all selected workflows that are not successful or failed" >&2

    for workflow in $workflows; do

        echo "Deleting <$workflow> history, please wait..." >&2
        $(dirname $0)/list.sh -w $workflow -o $older -s $status -r $repo | xargs -I{} gh run delete {}
    done
fi

rm -rf $workflows_temp
echo "Done." >&2
