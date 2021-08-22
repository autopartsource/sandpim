# SandPIM

SandPIM is a simple, LAMP-based Product Information Management system built around the AutoCare Association's ACES and PIES standards. The "Sand" in the name is a reference to the Sandpiper protocol that is starting to take shape in the AutoCare community (as of mid 2021). SandPIM serves a platform for testing concepts core to Sandpiper as they are being debated before adoption. SandPIM is intentionally written with minimal layers of abstraction and without a third-party framework. This is to lower the barriers to entry for a casual experimenter or contributor. 

This project is in the early stages and is not ready for use in the real world as an actual PIM. 
AutoPartSource's goal is to have it built out to the point of usability in Q4 of 2021. If you are interested in
contributing in any way (even just offering opinions!), please don't hesitate to reach out.

---

## Features

- Catalog fitment management based on Make-Model-Year and/or Mfr-Equipment
- Digital Asset management with integration to AWS S3
- Part attribute (PAdb) management
- Pricesheet management
- Competitor interchange management
- On-the-fly validation of data
- ACES & PIES xml exports
- ACES & PIES xml imports
- Sandpiper API server and client


--- 


## To-Do list

- Publish deployment how-to document for Fedora Linux 
- Publish VirtualBox VM on S3 for public download of fully functional demo server
- API client features to consume Content via Sandpiper protocol
