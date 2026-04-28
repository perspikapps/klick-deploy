#!/bin/sh

# Init default values
workflow=""
older=30
status="failure,cancelled"
repo=$(git config --get remote.origin.url | sed -E 's/.*[:\/]([^\/]+\/[^\.]+)(\.git)?$/\1/')

# handle arguments with getopt
OPTSTRING="w:o:s:r:"
while getopts ${OPTSTRING} opt; do
    case ${opt} in
    w)
        workflow=${OPTARG}
        echo "Selected workflow: ${workflow}" >&2
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

# Format the status string to be used in jq
status=$(echo $status | tr '[:upper:]' '[:lower:]' | sed 's/,/|/g')

# Log the selected options
echo "Listing ${workflow:-all} workflows older than ${older} days with status ${status}" >&2

gh run list --limit 500 ${workflow:+--workflow $workflow} --jq " .[]| select(.conclusion| test(\"$status\"))|
            select(.createdAt < (now-( $older * 86400) | strftime(\"%Y-%m-%dT%H-%M-%SZ\") )) |
            .databaseId" --json conclusion,databaseId,createdAt -R $repo
