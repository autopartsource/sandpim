# sandpim

SandPIM is a simple, LAMP-based Product Information Management system built around the AutoCare Association's ACES and PIES standards. The "Sand" in the name is a reference to the Sandpiper protocol that is starting to take shape in the AutoCare community (as of early 2020). SandPIM serves a platform for testing concepts core to Sandpiper as they are being debated before adoption. SandPIM is intentionally written with minimal layers of abstraction and without a third-party framework. This is to lower the barriers to entry for a casual experimenter or contributor. 

This project is in the early stages and is not ready for use in the real world as an actual PIM. 
AutoPartSource's goal is to have it built out to the point of usability in Q1 of 2020. 
We are getting organized and developing our Git workflow. If you are interested in
contributing in any way (even just offering opinions!), please don't hesitate to reach out.

---

## Planned Features

- Catalog fitment management based on Make-Model-Year and/or Mfr-Equipment
- Digital Asset management with integration to AWS S3
- Part attribute (PAdb) management
- Competitor interchange management
- On-the-fly validation of data
- ACES & PIES xml exports
- ACES & PIES xml imports
- Sandpiper API server and client

--- 


## To-Do list

- PAdb attribute display/edit in showPart.php
- note selection bank maintenance
- migrate notes from free-form to selection
- qdb fitmet attribute support in showApp.php
- vcdb-validation an app on the fly (uppon change)
- Part search by PAdb attribute
- copy-forward to next model-year on apps grid (showAppsByBasevehicle.php)
- fitment assets (show on the right side of showApp.php)
- export of ACES files
- export of PIES files
- user permission mechanism
- utility to duplicate apps from one basevehicle to another
- utility to duplicate apps from one partnumber to another
- fitment logic analysis on the fly (app grid page)
- background auditing and reporoting of parts
- background auditing and reporoting of apps
- API service to expose sandpiper objects
- API client features to consume sandpiper objects

## Installation notes
- SELinux allow uploads chcon unconfined_u:object_r:httpd_sys_rw_content_t:s0 PIESuploads/