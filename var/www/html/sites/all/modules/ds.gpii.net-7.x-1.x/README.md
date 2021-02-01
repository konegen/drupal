# github for Drupal 7

github allows you to use your github repositories as entities in
Drupal.

## Getting Started

github uses the Remote Entity API to fetch and store your repositories
as entities on your Drupal 7 site.

### Prerequisites

github depends on several third-party modules:

+ [remote_entity](https://www.drupal.org/project/remote_entity)
+ [clients](https://www.drupal.org/project/clients)
+ [efq_views](https://www.drupal.org/project/efq_views)

### Installation

Download the module from the [contributed Drupal modules page]
(http://drupal.org/project/github) and install on your Drupal 7
website.

## Configuration

Navigate to `admin/config/development/github` and enter your personal
access token to authorize the plugin to fetch your starred repositories.

## Display

Currently we only support viewing your repositories from `/github.com/` + FULL
REPO NAME such as `/github.com/davidgreiner/github`.

Note: They have to be starred by your profile, otherwise it will not work!

## To-Do

- [ ] Allow more authorization methods
- [ ] List view of all repositories
- [ ] Option to use watch and fork list
- [x] Get entity reference to work
- [ ] Option to use stats of source or fork repo
