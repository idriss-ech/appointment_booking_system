<img src="https://github.com/user-attachments/assets/86800886-85b5-4b54-9244-d2b4b1f3f063" width="200px"/>
![image](https://github.com/user-attachments/assets/7718ece9-b303-4b4a-9049-13a1aba81099)
[![Drupal](https://img.shields.io/badge/Drupal-10+-%230678BE?logo=drupal)](https://www.drupal.org)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-Yes-green.svg)](https://github.com/your-org/appointment-booking-system/graphs/commit-activity)
[![PHP](https://img.shields.io/badge/PHP-8.3+-%23777BB4?logo=php)](https://php.net/)

<hr>


# Appointment Booking System - Drupal Module

## Overview

The **Appointment Booking System** is a custom Drupal module designed to facilitate appointment scheduling with advisers at different agencies. The module provides a multi-step booking process, appointment management, and an administrative dashboard.

## Features

- Multi-step booking process
- Appointment modification and cancellation
- Adviser and agency management
- Role-based access control
- Email notifications for confirmations and reminders
- Administrative interface for appointment tracking
- Security best practices (CSRF protection, input validation, etc.)

## Installation

### Requirements

- Drupal 9/10
- PHP 8+
- MySQL 5.7+ / MariaDB 10.3+
- fullcalendar_view

```bash
composer require 'drupal/fullcalendar_view:^5.1'
```

- office_hours

```bash
composer require 'drupal/office_hours:^1.23'
```

### Installation Steps

1. Download the module and place it in `modules/custom/`.
2. Enable the module via the Drupal admin panel or using Drush:

   ```sh
   drush en appointment_booking -y
   ```

3. Clear the cache:
   ```sh
   drush cr
   ```

## Administrative Features

- View and manage appointments at `/admin/structure/appointment`
- Modify, cancel, and export appointments
- Set up advisers and their availability
- Set up agencies

####  🏦 Agencies : Add, Edit, Delete

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/1f88ea8f-c72b-483f-b793-d4d366c79170" />



<img width="1440" alt="image" src="https://github.com/user-attachments/assets/a1a398ab-a69f-4215-afbe-57f75d6d990e" />
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/e6d1ea77-fbe6-4344-aedb-562553ac36aa" />

#####  ⌛️ Appointments : Add, Edit, Delete, Filters, Export Csv

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/cb47e725-0e7f-41a3-a1de-dec5568d7d78" />

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/675b55ad-98ea-4adb-aef7-1ec461d94fd2" />

Exported Appointments :
```csv
Title,"Start Date","End Date",Agency,Adviser,Status
Check-up,"Thursday, 27 March 2025 : 11:30 - 13:00",Void,mohamed,pending
Follow-up,"Friday, 28 March 2025 : 09:30 - 11:30",Void,mohamed,pending
```
####  🏦 Advisers : Add, Edit, Delete

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/68a96327-1c98-4d00-bf67-ba992a3be884" />

## Technical Details

### Custom Entities

- **Appointment** (`appointment`)
- **Agency** (`agency`)
- **Adviser** (Extended `user` entity)

<img width="691" alt="image" src="https://github.com/user-attachments/assets/d7b6fbc9-56e2-4891-aa49-7d91f139b8e6" />

### Module Structure
```
.
├── appointment_booking.info.yml
├── appointment_booking.install
├── appointment_booking.libraries.yml
├── appointment_booking.module
├── appointment_booking.permissions.yml
├── appointment_booking.routing.yml
├── appointment_booking.services.yml
├── config
│   └── install
├── css
│   └── global.css
├── js
│   ├── fullcalendar.js
│   └── global.js
├── src
│   ├── Access
│   ├── AdviserListBuilder.php
│   ├── AgencyListBuilder.php
│   ├── AppointmentListBuilder.php
│   ├── Controller
│   ├── Entity
│   ├── Form
│   ├── Plugin
│   └── Service
└── templates
    ├── adviser-selection.html.twig
    ├── agency-selection.html.twig
    ├── appointment-confirmation-appointment.html.twig
    ├── appointment-confirmation-email.html.twig
    ├── appointment-confirmation-profile.html.twig
    ├── appointment-information.html.twig
    ├── appointment-item.html.twig
    ├── appointment-success-message.html.twig
    └── appointment-type-selection.html.twig

13 directories, 22 files
```



## Booking Process

1. **Select an agency**: Users choose an agency.

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/51b9848c-1901-462d-900d-22aab28436c6" />

3. **Choose appointment type**: Select a service category.

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/f54f15f4-2d1d-4003-99b0-dd1457012072" />

4. **Select an adviser**: Filter advisers by agency.
   <img width="1440" alt="image" src="https://github.com/user-attachments/assets/d112c4fb-4dc1-47b8-9912-023c55ac3d95" />

5. **Pick a date and time**: Choose an available slot.

<img width="1439" alt="image" src="https://github.com/user-attachments/assets/f6a85494-7e81-4846-b54d-8508c799be7b" />

6. **Enter personal details**: Provide name, email, and phone number.

<img width="1433" alt="image" src="https://github.com/user-attachments/assets/0bd69470-28b3-43b2-a838-b0b230247b53" />

7. **Confirm appointment**: Review details and confirm.

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/a53787a7-a8ba-4a8c-843a-06b56b43682c" />

7. **Edit Profile**: Edit user infos

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/3e3bf3a6-d967-44dd-9268-f0cb7417de03" />

7. **Edit Date**: Edit appointment date

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/cb09e26b-7bc0-4cc0-b264-5aa6ed036b14" />

8. **Save appointment**: success message

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/be8e21b8-aa73-4e3f-bf1c-562951712b24" />


9. **Email Notification**: Notify the user about their appointment.

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/9e013337-148f-4df8-a6c7-1f94b7e64dd4" />

10. **Update the appointment**: using the user's phone number.


<img width="1439" alt="image" src="https://github.com/user-attachments/assets/1638a340-6366-4f76-81b1-d256fbb48fc1" />

11. **Verify if the user is the owner of the phone number**.

<img width="1439" alt="image" src="https://github.com/user-attachments/assets/2331b2cb-7f41-4536-b175-644703419972" />
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/717b6945-74ef-470c-b9ca-b9c2cd4e7297" />
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/70d14210-9843-4d08-851b-061e185520f2" />

12. **Update Notification**

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/b35dc918-e428-4ab8-b905-dddf4aae7a3a" />


### Translation : 
Example : 

#### 🇫🇷 French

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/596b7112-0ca3-4d8f-a0f4-dffa15e2f03f" />

#### 🇺🇸 English
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/dd9be74d-4dbf-4067-8026-72f81c0ba4de" />



### Security Measures

- **Input Validation**: Sanitization applied to user input.

<img width="1425" alt="image" src="https://github.com/user-attachments/assets/457d5e47-7512-4548-8056-7a5ba35eb67b" />


- **Role-Based Access Control**: Permissions set via `appointment.permissions.yml`.

You need to be authenticated if you want to make or view your appointment.

<img width="1437" alt="image" src="https://github.com/user-attachments/assets/9190a3fb-8dc8-4759-8725-f262ff783ffa" />

### Performance Optimization

- Caching enabled for appointment listings.
- Batch processing used for data exports.
- Optimized database queries.

## Development Roadmap

### Phase 1: Foundation

- Module setup
- Custom entity creation
- Basic CRUD operations

### Phase 2: Core Functionality

- Multi-step booking form
- Appointment views
- Basic email notifications

### Phase 3: Advanced Features

- Calendar integration (`fullcalendar.io`)
- Adviser working hours management
- Improved email notifications

### Phase 4: Finalization & Testing

- UI/UX improvements
- Security testing
- Documentation update


## Contact

For issues or contributions, reach out via [GitHub Repository](https://github.com/idriss-ech/appointment_booking_system).
