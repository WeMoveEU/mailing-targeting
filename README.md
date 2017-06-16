# CiviCRM Mailing targeting

This is a CiviCRM extension aiming at making it easier to define the target recipients of mailings.
The extension provides:

 - A replacement of the "Send email" search task that lets users associate a search result to
   + a new mailing, like the core task
   + but also an existing draft 
   + or a new clone of a previous mailing.
 - A custom search with
   + a list of include conditions combined with `AND`
   + a list of exclude conditions combined with `OR`
   + conditions can be:
     - being part of a group
     - being the recipient of a mailing
     - having a signature activity for a campaign
   + for each list of conditions, a list of common is suggested for a one-click pick
