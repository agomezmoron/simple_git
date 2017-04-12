# Simple Git Module

Module to work with the GitHub, GitLab, etc REST API from Drupal exposing a REST service to be used from another clients (Angular, Ionic, etc).

It also supports OAuth integrations.

## REST Services exposed

The REST API base PATH will be: **/api/simple_git**

### Connector

REST service to retrieve the configured connectors to work with GitHub, GitLab, etc.

**Path:** /connector

| Method  | Parameters | Response | Description |
| ------------- | ------------- | ------------- | ------------- |
| GET  | None  | [{"client_id": "XXXX", "type": "GITHUB"}] | Returns the Git App Client ID and the Git Service type.  |

### Account

REST service to link and retrieve accounts information.

**Path:** /account

| Method  | Parameters | Response | Description |
| ------------- | ------------- | ------------- | ------------- |
| GET  |  **/all** | All the associated accounts:  [{ "id": 1, "fullname": "Alejandro Gómez Morón", "username": "agomezmoron", "email": "amoron@emergya.com", "photoUrl": "http://lorempixel.com/200/200/", "repoNumber": 10, "organization": "Emergya", "location": "Sevilla" }] | It returns all the associated accounts  |
| GET  | **/{account_id}** | { "id": 1, "fullname": "Alejandro Gómez Morón", "username": "agomezmoron", "email": "amoron@emergya.com", "photoUrl": "http://lorempixel.com/200/200/", "repoNumber": 10, "organization": "Emergya", "location": "Sevilla" } | It returns the associated account  |
| POST  | { "code": "ABCD", "nonce": "EDFG", "type": "GITHUB"} | { "id": 3, "fullname": "Alejandro Gómez Morón", "username": "agomezmoron", "email": "amoron@emergya.com", "photoUrl": "http://lorempixel.com/200/200/", "repoNumber": 10, "organization": "Emergya", "location": "Sevilla" } | It connects to GitHub and returns the linked account information.  **If the authentication fails, an error with a 401 status code will be raised.** | 
| DELETE  | **/{account_id}** | NONE | It deletes the provided {account_id}. |

### Pull Requests

**Path:** /pull_request

| Method  | Parameters | Response | Description |
| ------------- | ------------- | ------------- | ------------- |
| GET  | None  | [{"title": "Pull Request 1 Title", "description": "Pull Request description gfjdngfkjdnbjdkjnfvjdn", "userName": "UserName1", "date": "10 months", "commits": 312, "comments": 129, "count": 582, "from": "MB-1685-DEV_Fix", "to": "Master_branch_of_project" }] | Returns all the available Pull Requests.  |

### Repository

**Path:** /repository

| Method  | Parameters | Response | Description |
| ------------- | ------------- | ------------- | ------------- |
| GET  |  **/{account_id}/{repository_id}** | All the associated accounts:  [{ "id": 1, "fullname": "Alejandro Gómez Morón", "username": "agomezmoron", "email": "amoron@emergya.com", "photoUrl": "http://lorempixel.com/200/200/", "repoNumber": 10, "organization": "Emergya", "location": "Sevilla" }] | It returns all the associated accounts  |
| GET  |  **/all/all** | All the associated accounts:  [{ "id": 1, "fullname": "Alejandro Gómez Morón", "username": "agomezmoron", "email": "amoron@emergya.com", "photoUrl": "http://lorempixel.com/200/200/", "repoNumber": 10, "organization": "Emergya", "location": "Sevilla" }] | It returns all the associated accounts  |

### User

**Path:** /user

| Method  | Parameters | Response | Description |
| ------------- | ------------- | ------------- | ------------- |
| GET  |  **/{account_id}/{user}** | All the associated accounts:  [{ "id": 1, "fullname": "Alejandro Gómez Morón", "username": "agomezmoron", "email": "amoron@emergya.com", "photoUrl": "http://lorempixel.com/200/200/", "repoNumber": 10, "organization": "Emergya", "location": "Sevilla" }] | It returns all the associated accounts  |
| GET  |  **/all/{user}** | All the associated accounts:  [{ "id": 1, "fullname": "Alejandro Gómez Morón", "username": "agomezmoron", "email": "amoron@emergya.com", "photoUrl": "http://lorempixel.com/200/200/", "repoNumber": 10, "organization": "Emergya", "location": "Sevilla" }] | It returns all the associated accounts  |


### Collaborator

**Path:** /collaborator

| Method  | Parameters | Response | Description |
| ------------- | ------------- | ------------- | ------------- |
| GET  |  **/{account_id}/{repository}/{collaborator}** | All the collaboratos of associated accounts:  ["id": 1, "username" => 'estefaniabarrera', 'photoUrl' => 'avatar_url',] | It returns all the collaboratorassociated accounts  |
| DELETE  |  **/{account_id}/{repository}/{collaborator}** | NONE  |
| PUT  |  **/{account_id}/{repository}/{collaborator}** | NONE  |
