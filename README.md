# Extending Sprout Email
**Sprout Email** was designed from the ground up to be robust and extensible.

## Dynamic Events
**Dynamic Events** are special classes that can be registered as event handlers at runtime, enabling **Sprout Email** to respond to such events even if it originally did not know about them.
These classes serve two purposes:
1. To integrate a **UI** that enables users to create notification triggers
2. To enable **Sprout Email** to `send notifications` for this event type  

### Creating a Dynamic Event
To create a **Dynamic Event** you must extend the self documented **SproutEmailBaseEvent**.
This `abstract` class follows many Craft conventions and should provide enough documentation to get you started.
