# Notification Templates

This document contains the current app-wide default notification messages for review and approval.

Teams, services, and sub-services can override these defaults. The resolution order is:

1. Sub-service template
2. Service template
3. Team template
4. App-wide default
5. Built-in fallback message

The app-wide defaults are editable by superadmins. Team, service, and sub-service templates can be maintained by users with the appropriate team permissions.

## Client Messages

Clients receive only new-request and resolved or closed notifications. Client messages do not include a link to the request.

### Request Created

#### English

##### Subject

Your accessibility request `{{requestid}}` has been received

##### Message

Hello `{{client_fname}} {{client_lname}}`,

Your accessibility request `{{requestid}}` has been received.

We will review it and contact you if more information is needed.

Request title: `{{requesttitle}}`

Thank you very much,
`{{teamname}}`
`{{teamemail}}`
Accessibility, Accommodation and Adaptive Computer Technology (AAACT)
Digital Transformation Canada

#### French

##### Subject

Votre demande d'accessibilité `{{requestid}}` a été reçue

##### Message

Bonjour `{{client_fname}} {{client_lname}}`,

Votre demande d'accessibilité `{{requestid}}` a été reçue.

Nous l'examinerons et nous communiquerons avec vous si des renseignements supplémentaires sont nécessaires.

Titre de la demande : `{{requesttitle}}`

Merci beaucoup,
`{{teamname}}`
`{{teamemail}}`
Accessibilité, adaptation et technologie informatique adaptée (AATIA)
Transformation numérique Canada

### Resolved / Closed

#### English

##### Subject

Your accessibility request `{{requestid}}` has been resolved

##### Message

Hello `{{client_fname}} {{client_lname}}`,

Your request `{{requestid}}` has been resolved.

If you believe more work is required, reply to this message and reference your request number.

We would love to hear how we did. Please fill out this short survey: `{{survey_link_en}}`

Thank you very much,
`{{teamname}}`
`{{teamemail}}`
Accessibility, Accommodation and Adaptive Computer Technology (AAACT)
Digital Transformation Canada

#### French

##### Subject

Votre demande d'accessibilité `{{requestid}}` a été résolue

##### Message

Bonjour `{{client_fname}} {{client_lname}}`,

Votre demande `{{requestid}}` a été résolue.

Si vous croyez que d'autres travaux sont nécessaires, répondez à ce message et mentionnez votre numéro de demande.

Nous aimerions savoir comment s'est déroulée votre expérience. Veuillez remplir ce court sondage : `{{survey_link_fr}}`

Merci beaucoup,
`{{teamname}}`
`{{teamemail}}`
Accessibilité, adaptation et technologie informatique adaptée (AATIA)
Transformation numérique Canada

## Employee Messages

Employee messages include the request link so internal recipients can open the request after signing in.

### Request Created

#### English

##### Subject

New accessibility request `{{requestid}}` assigned to your team

##### Message

Hello `{{teamname}}`,

A new accessibility request `{{requestid}}` has been assigned to your team.

Request title: `{{requesttitle}}`
Catalogue: `{{catalogue_name}}`
Service: `{{service_name}}`

View request: `{{url}}`

Thank you very much,
`{{teamname}}`
`{{teamemail}}`
Accessibility, Accommodation and Adaptive Computer Technology (AAACT)
Digital Transformation Canada

#### French

##### Subject

Nouvelle demande d'accessibilité `{{requestid}}` assignée à votre équipe

##### Message

Bonjour `{{teamname}}`,

Une nouvelle demande d'accessibilité `{{requestid}}` a été assignée à votre équipe.

Titre de la demande : `{{requesttitle}}`
Catalogue : `{{catalogue_name}}`
Service : `{{service_name}}`

Voir la demande : `{{url}}`

Merci beaucoup,
`{{teamname}}`
`{{teamemail}}`
Accessibilité, adaptation et technologie informatique adaptée (AATIA)
Transformation numérique Canada

### Assigned

#### English

##### Subject

Accessibility request `{{requestid}}` assigned to `{{teamname}}`

##### Message

Hello `{{teamname}}`,

Accessibility request `{{requestid}}` has been assigned to `{{teamname}}`.

Review the request context and confirm ownership with your team.

View request: `{{url}}`

Thank you very much,
`{{teamname}}`
`{{teamemail}}`
Accessibility, Accommodation and Adaptive Computer Technology (AAACT)
Digital Transformation Canada

#### French

##### Subject

Demande d'accessibilité `{{requestid}}` assignée à `{{teamname}}`

##### Message

Bonjour `{{teamname}}`,

La demande d'accessibilité `{{requestid}}` a été assignée à `{{teamname}}`.

Examinez le contexte de la demande et confirmez la prise en charge avec votre équipe.

Voir la demande : `{{url}}`

Merci beaucoup,
`{{teamname}}`
`{{teamemail}}`
Accessibilité, adaptation et technologie informatique adaptée (AATIA)
Transformation numérique Canada

### Resolved / Closed

#### English

##### Subject

Accessibility request `{{requestid}}` marked as resolved

##### Message

Hello `{{teamname}}`,

Accessibility request `{{requestid}}` has been marked as resolved.

Ensure any final records or follow-up actions are complete.

View request: `{{url}}`

Thank you very much,
`{{teamname}}`
`{{teamemail}}`
Accessibility, Accommodation and Adaptive Computer Technology (AAACT)
Digital Transformation Canada

#### French

##### Subject

Demande d'accessibilité `{{requestid}}` marquée comme résolue

##### Message

Bonjour `{{teamname}}`,

La demande d'accessibilité `{{requestid}}` a été marquée comme résolue.

Assurez-vous que les dossiers finaux et les actions de suivi sont complets.

Voir la demande : `{{url}}`

Merci beaucoup,
`{{teamname}}`
`{{teamemail}}`
Accessibilité, adaptation et technologie informatique adaptée (AATIA)
Transformation numérique Canada

### Status / Details Updated

#### English

##### Subject

Status update for accessibility request `{{requestid}}`

##### Message

Hello `{{teamname}}`,

The status of request `{{requestid}}` has changed to `{{status_label}}`.

Please review the latest details using the request link below.

View request: `{{url}}`

Thank you very much,
`{{teamname}}`
`{{teamemail}}`
Accessibility, Accommodation and Adaptive Computer Technology (AAACT)
Digital Transformation Canada

#### French

##### Subject

Mise à jour du statut de la demande `{{requestid}}`

##### Message

Bonjour `{{teamname}}`,

Le statut de la demande `{{requestid}}` a changé pour `{{status_label}}`.

Veuillez consulter les derniers détails en utilisant le lien ci-dessous.

Voir la demande : `{{url}}`

Merci beaucoup,
`{{teamname}}`
`{{teamemail}}`
Accessibilité, adaptation et technologie informatique adaptée (AATIA)
Transformation numérique Canada

## Special Internal Routing Messages

These messages are internal routing notifications. They are not part of the editable client and employee template set.

### After-fact Request

This message is sent when an accessibility request is submitted after the work has already been completed.

#### English

##### Subject

After-fact accessibility request `{{requestid}}` assigned to your team

##### Message

Hello `{{teamname}}`,

A new accessibility request `{{requestid}}` was submitted after the work already happened and has been assigned to your team.

Request title: `{{requesttitle}}`
Catalogue: `{{catalogue_name}}`
Service: `{{service_name}}`

View request: `{{url}}`

#### French

##### Objet

Demande d'accessibilité après-fact `{{requestid}}` assignée à votre équipe

##### Message

Bonjour `{{teamname}}`,

Une nouvelle demande d'accessibilité `{{requestid}}` a été soumise après la réalisation des travaux et a été assignée à votre équipe.

Titre de la demande : `{{requesttitle}}`
Catalogue : `{{catalogue_name}}`
Service : `{{service_name}}`

Voir la demande : `{{url}}`

### AAACT Triage Required

This message is sent when a request cannot be routed to a configured team and requires central AAACT triage.

#### English

##### Subject

Accessibility request `{{requestid}}` needs AAACT triage

##### Message

Hello `{{teamname}}`,

A new accessibility request `{{requestid}}` needs AAACT triage.

Review the request details and route it to the appropriate team.

Request title: `{{requesttitle}}`

View request: `{{url}}`

#### French

##### Objet

Demande d'accessibilité `{{requestid}}` à faire trier par AATIA

##### Message

Bonjour `{{teamname}}`,

Une nouvelle demande d'accessibilité `{{requestid}}` requiert un triage AATIA.

Consultez les détails de la demande et acheminez-la à l'équipe appropriée.

Titre de la demande : `{{requesttitle}}`

Voir la demande : `{{url}}`

## Available Placeholders

- `{{requestid}}`: Request ID
- `{{requesttitle}}`: Request title
- `{{assignee}}`: Assigned employee name, available in assignment notifications
- `{{teamname}}`: Responsible team name
- `{{teamemail}}`: Responsible team email address
- `{{catalogue_name}}`: Catalogue or topic name
- `{{service_name}}`: Service name
- `{{status_label}}`: Current status label
- `{{client_fname}}`: Client first name
- `{{client_lname}}`: Client last name
- `{{url}}`: Employee request link
- `{{survey_link_en}}`: English client survey link
- `{{survey_link_fr}}`: French client survey link

The `{{salutation}}` and `{{signature}}` placeholders are not supported. Greeting and signature text should be written directly in the template.
