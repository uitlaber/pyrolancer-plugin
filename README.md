# Pyrolancer - Freelance Marketplace Plugin

Pyrolancer is [a plugin for October CMS](https://octobercms.com/plugin/responsiv-pyrolancer) used to create an **online services marketplace**, a platform that allows people to connect about online services. You can set up your own fully functioning marketplace in the time it takes to install the script.

## Features

- Single profile for each worker, using multiple skills.
- Online portfolio
- Geo-locating for projects

## Activity Feed

There is an activity feed located on the homepage and on the user dashboard which can contain the following events. Events considered private will only appear on the user dashboard. Events that are public will appear on the home page.

- **user-created**: When a user first signs up (private).
- **worker-created**: When a user creates a worker profile.
- **portfolio-created**: When a worker creates a portfolio.
- **project-created**: When a client creates a new project.
- **project-message**: When a user asks a question about a project.
- **project-bid**: When a worker submits a bid on a project.

## Notification Emails

These are the notification emails that are sent using Pyrolancer.

### Admin templates

- `project-approval-request`: A project has been submitted to the website and requires approval.
- `project-reapproval-request`: A previously rejected project has been resubmitted for approval.

### Public templates

- `client-bid-confirmed`: Sent to the project owner when a project enters development status.
- `client-bid-declined`: Sent to the project owner when the worker was chosen for a project (auction) but declined the offer.
- `client-digest`: Sent to a client when a new bid or question is placed on their project. Max 1 per hour.
- `client-project-approved`: Sent to the user when their project has been approved and is now visible on the website.
- `client-project-expired`:  Sent to the client when one of their projects has expired.
- `client-project-rejected`: The project has been rejected and needs attention before being resubmitted.
- `worker-alert`: A summary of a single project sent to a worker when an urgent project is submitted.
- `worker-digest`: A compilation of projects that relate to the worker, sent in the form of a digest email, often daily. Max 1 per day.
- `worker-testimonial-complete`: Sent to the worker when a previous client has left a testimonial about them.
- `worker-testimonial-request`: Sent to the worker's previous client or workplace, requesting they submit a testimonial about the worker.
- `worker-bid-accepted`: Sent to the worker when their bid on a project was accepted by the client.
- `collab-message`: Sent when a user submits a new message to the project collaboration area.
- `collab-update`: Sent when a user updates an exisiting collaboration area message with a major update.
- `collab-terminated`: Someone terminated the project collaboration.
- `collab-complete`: Someone marked the project collaboration as complete.
- `collab-review`: Sent to the user when a review is left about them.

## Status Workflow

A project can exist in one of the following statuses:

- Auction phase
    + *Draft*: The default status, before a project has been submitted.
    + *Pending*: Project has been submitted for approval.
    + *Rejected*: Project has been rejected with a reason.
    + *Active*: Project is visible to the public.
    + *Suspended*: Project has been forcibly hidden by an administrator.
    + *Expired*: Project reached expire period and no worker was chosen.
    + *Cancelled*: Project was cancelled by the owner.

- Selection phase
    + *Wait*: A worker has been selected, waiting for confirmation.
    + *Declined*: The selected worker has declined the job.

- Development phase
    + *Development*: Project is under development.
    + *Terminated*: Project was terminated by either party.
    + *Completed*: Project has been completed, waiting for reviews.
    + *Closed*: Project has finished, reviews have been made.

### Wait

The worker has been chosen and should confirm the acceptance. If the worker does not reply within 24 hours, the client has the opportunity to select someone else. If nothing happens for a calendar month (30 days), the project moves to **Cancelled** status.

### Completed

Project is held in this status for 2 weeks (14 days). During this time the client or freelancer can modify their reviews, then they are locked. If no review is left, an automatic 5 star review is given with no comments.

## Project Flags

A project has boolean flags:

- `is_active`: Project can receive bids and contacts.
- `is_approved`: Project has recevied approval.
- `is_featured`: Project appears as featured.
- `is_urgent`: Project appears as urgent, email notitications sent immediately.
- `is_sealed`: Bids and contacts are hidden from other users.
- `is_private`: Project can only be viewed by registered users.
- `is_hidden`: Project does not appear in the main list of projects.

## Fee opportunities

There are several areas where a fee may be collected. Some examples:

- Cost to submit a project

- Monthly fee for workers to contact clients

- Percentage of final project value

### How to implement payment options

Download the [Responsiv.Pay](https://octobercms.com/plugin/responsiv-pay) plugin that will provide payment gateways and invoicing options.
