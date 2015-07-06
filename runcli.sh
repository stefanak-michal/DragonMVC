#! /bin/bash

BASE=$(dirname $0)
config=$BASE"/config/.runcli"
RED="\e[31m"
YELLOW="\e[33m"
GREEN="\e[32m"
DIM="\e[2m"
NC="\e[0m"

function missing() {
	echo -e "${RED}Missing arguments!"
	echo -e $NC$DIM
	echo "-----------------------------------------------------------------------------"
	echo "| ./runcli.sh -v scriptname                                                 |"
	echo "| -v           verbose output                                               |"
	echo "| scriptname   file name of php script                                      |"
	echo "-----------------------------------------------------------------------------"
	echo -e $NC
	exit
}

OUTPUT=false
FILE=""

#resolve arguments
if [ $# -gt 0 ]; then
	if [ ${1:0:1} = "-" ]; then
		#check scriptname
		if [ $# != 2 ]; then 
			missing
		else
			FILE=$2
		fi
		
		if [ `expr index "$1" v` != 0 ]; then
			OUTPUT=true
		fi
		
		#another arguments
	else
		FILE=$1
	fi
else
	missing
fi

if $OUTPUT; then
	echo -e "${GREEN}Started ..${NC}"
fi

#check config file
if [ -s "$config" ]; then
	source "$config"
else
	if $OUTPUT; then
		echo -e $DIM
		echo "-----------------------------------------------------------------------------"
		echo "| Config file missing, using default.                                        |"
		echo "| If you need create file {current dir}/config/.runcli with these variables: |"
		echo "| php=\"Path to php executable (optional)\"                                    |"
		echo "| env=\"development OR production (optional, default: production)\"            |"
		echo "|                                                                            |"
		echo "| Make the file utf-8 encoding w/o BOM and Unix EOL                          |"
		echo "-----------------------------------------------------------------------------"
		echo -e $NC
	fi
fi

#check environment
if [ -z "$env" ]; then
	if $OUTPUT; then
		echo -e "${YELLOW}Not defined environment, using \"production\"${NC}"
		echo 
	fi
    env="production"
fi

FILE=$BASE"/scripts/"$FILE
if [ ${FILE: -4} != ".php" ]; then
	FILE=$FILE".php"
fi

if [ -f "$FILE" ]; then
	if $OUTPUT; then
		echo -e "${GREEN}Executes PHP file $FILE $NC"
		echo 
	fi

	#if is executable php interpretor in global path
    if which php > /dev/null; then
		if $OUTPUT; then
			php "$FILE" "$env"
		else
			php "$FILE" "$env" > /dev/null 2>&1
		fi
	#else is PHP interpretor defined in config file
    elif [ -z "$php" ]; then
		if $OUTPUT; then
			"$php $FILE $env"
		else
			"$php $FILE $env" > /dev/null 2>&1
		fi
    else
		if $OUTPUT; then
			echo -e "${RED}PHP executable interpretor not found!"$NC
		fi
		exit
    fi
else
	if $OUTPUT; then
		echo -e "${RED}Script file $FILE not found!"$NC
	fi
	exit
fi
