# RadicalMart Shipping: Standard

**RadicalMart Shipping: Standard** is a shipping plugin for RadicalMart that provides a **general-purpose manual shipping method** with configurable address fields, fixed shipping prices, and customer delivery details.

The plugin is designed for stores that need a built-in shipping method without external delivery service integration. It collects shipping information from the customer, formats delivery data for the order, and applies configured shipping prices inside RadicalMart.

It works as part of RadicalMart's shipping architecture and does not change order lifecycle logic.

---

## Purpose

This plugin provides a **standard shipping method** for RadicalMart.

Its responsibility is limited to:

* collecting customer shipping address data
* applying configured shipping prices
* formatting delivery information for checkout, order management, and personal account views
* storing manual delivery details inside the order

All order processing remains controlled by RadicalMart core.

---

## What this plugin does

* Registers a built-in shipping method for RadicalMart and RadicalMart Express
* Collects structured shipping address fields such as country, region, city, postal code, street, house, and additional address details
* Supports configurable field visibility and required status
* Supports default values for shipping fields
* Applies fixed shipping prices based on configured method prices
* Supports manual shipping price recalculation during order editing
* Formats shipping address data into readable order information
* Supports additional delivery fields such as delivery date and customer comment
* Provides checkout, order, and personal account forms and layouts for the shipping method

---

## What this plugin does NOT do

* ❌ Does not integrate with external delivery APIs
* ❌ Does not calculate delivery by route, map, or carrier tariffs
* ❌ Does not manage inventory or warehouse logistics
* ❌ Does not replace RadicalMart shipping orchestration

The plugin functions as a **manual shipping method implementation**.

---

## Architecture role

Within RadicalMart shipping architecture:

```
Customer Shipping Data
        ↓
 Shipping Plugin (Standard)
        ↓
 Fixed Price and Address Preparation
        ↓
 RadicalMart Order
```

This plugin represents a **default internal shipping method**.

---

## Shipping flow

1. The customer selects the shipping method during checkout.
2. The plugin displays the configured shipping fields.
3. The customer enters delivery address and optional delivery details.
4. The configured shipping price is applied.
5. The address and shipping data are formatted and stored with the order.
6. The shipping information is shown in checkout, order views, and the personal account area.

---

## Address handling

The plugin works with structured address fields, including:

* country
* region
* city
* postal code
* street
* house
* building
* entrance
* floor
* apartment
* comment

Each field can be configured as required, optional, or hidden.

Default values can also be defined for shipping fields.

---

## Pricing model

The plugin uses **configured internal shipping prices** rather than external carrier calculations.

This makes it suitable for scenarios such as:

* flat-rate shipping
* manually defined city delivery
* store pickup with optional shipping price
* simple internal courier methods

During order editing, shipping price can be recalculated manually when needed.

---

## Usage

This plugin is intended for:

* stores that need a simple built-in shipping method
* projects without carrier API integration
* delivery flows based on manually configured prices
* installations using both RadicalMart and RadicalMart Express

It can operate alongside other shipping plugins.

---

## Extensibility

Shipping behavior can be extended by:

* overriding checkout and personal account layouts
* adjusting field configuration and defaults
* reacting to RadicalMart shipping events around customer data and order totals
* combining the plugin with other shipping or order-processing extensions

The plugin follows RadicalMart's event-driven architecture.
