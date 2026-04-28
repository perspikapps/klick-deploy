# awk script to filter host entries based on a selector string
# Usage: selector.awk <selector> < input_file
# selector format: key1=value1&key2=value2

# --


# Initialize the script and parse the selector input
BEGIN { 

    # if selector contains |, generate an error
    if (index(selector, "|") > 0) {
        print "Error: selector operator '|' is not supported" > "/dev/stderr";
        exit 1;
    }

    # Treat "all" as an empty selector
    if (selector == "all") {
        selector = "";
    }

    # Split the selector into key-value pairs
    split(selector, kv_pairs, "&");
    for (i in kv_pairs) {
        split(kv_pairs[i], kv, "=");
        if (kv[1] != "all") {
            selectors[kv[1]] = kv[2];
        }
    }

    # Split labels into an array
    split(labels, label_list, ",");

    printf("[");
}

# Detect the start of the "hosts" section
/hosts:/ {h=1; next}

# Process host entries
/:/ && h { 
    hi=match($0, /[^ ]/)-1; 
    h=0; 
}
/:/ && hi { 
    s=match($0, /[^ ]/)-1; 
    if (s==0) {hi=0; next} 
    if (s==hi) { 
        gsub("[ ]+|[: ]+$", "", $0); 
        host=$0;
    } 
}

# Detect the start of the "labels" section for a host
/labels:/ && host { 
    l=1;     
    next; 
}

# Process label entries
/:/ && l { 
    li=match($0, /[^ ]/)-1; 
    l=0; 
}

/:/ && li { 
    s=match($0, /[^ ]/)-1; 
    if (s==li) { 
        gsub("[ ]+|[: ]+$", "", $0);  

        # Extract the label name and value
        split($0, label_current, ":");

        # add label_current to label_array
        label_array[label_current[1]] = label_current[2];

        # Skip validation if selectors array is empty
        if (length(selectors) == 0) {
            selected=length(selectors);
        }
        else {
            # Check if the label is selector criteria
            if (label_current[1] in selectors && selectors[label_current[1]] == label_current[2] ){
                selected=selected+1;     
            }
        }
    } 

    if (s!=li) {
        # if host is selected, print the host and labe
        if (selected == length(selectors)) {

            if (count > 0) {
                printf(",");
            }
            else {
                count=0;
            }

            printf("{\"host\":\"%s\"", host);

            if (length(label_list) > 0) {

                for (label in label_list) {

                    # if label_list[label] is empty, skip it
                    if (label_array[label_list[label]] == "") {
                        continue;
                    }

                    # if label_array[label_list[label]] is a json array
                    if (label_array[label_list[label]] ~ /^\[.*\]$/) {
                        printf(", \"%s\":%s", label_list[label], label_array[label_list[label]]);
                        continue;
                    }

                    # if label_array[label_list[label]] contains a comma, print it as a json array
                    if (label_array[label_list[label]] ~ /,/) {
                    
                        printf(", \"%s\":[", label_list[label]);
                        n = split(label_array[label_list[label]], label_values, ",");
                        for (i = 1; i <= n; i++) {
                            printf("\"%s\"", label_values[i]);
                            if (i < n) {
                                printf(", ");
                            }
                        }
                        printf("]");
                        continue;
                    } 

                    # if label_array[label_list[label]] does not contain a comma, print it as a string
                    printf(", \"%s\":\"%s\"",  label_list[label], label_array[label_list[label]]);
                }
            } 

            printf("}");

            count++;
        }
        
        # Resset variables for the next host
        li=0;
        selected=0;
        delete label_array;    
    }

}

# End of the script
END { 
    printf("]\n");
}
