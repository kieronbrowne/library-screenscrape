#!/usr/bin/python

import mechanize
import BeautifulSoup
import argparse
import datetime
import dateutil.parser

def daysTillDue(dueText):
    dueDate = dateutil.parser.parse(dueText)
    diff = dueDate - datetime.datetime.now()
    return diff.days + 1

parser = argparse.ArgumentParser(description='Richmond Library Loans List')
parser.add_argument("username")
parser.add_argument("password")
args = parser.parse_args()

br = mechanize.Browser()
br.set_handle_robots(False)
br.set_handle_refresh(False)

br.open("https://richmond.spydus.co.uk/cgi-bin/spydus.exe/MSGTRN/OPAC/HOME")

br.form = list(br.forms())[1]

br["BRWLID"] = args.username
br["BRWLPWD"] = args.password
response = br.submit()

resp = response.read()
soup = BeautifulSoup.BeautifulSoup(resp)

refreshTag = soup.find("meta", attrs={"http-equiv":"Refresh"})
detailsUrl = refreshTag['content'].split(";")[1][5:]

br.open(detailsUrl)

loansPage = br.follow_link(text_regex=r"Current loans")
resp = loansPage.read()
soup = BeautifulSoup.BeautifulSoup(resp)

table = soup.find("table")
for row in table.findAll("tr")[1:]:
   cells = row.findAll("td")
   print "%s: due back in %d days" % (cells[1].find('a').text, daysTillDue(cells[2].text)) 
