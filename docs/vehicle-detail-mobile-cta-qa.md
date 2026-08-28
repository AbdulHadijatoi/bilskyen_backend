# Vehicle detail mobile CTA — manual QA checklist

Use this checklist when verifying lead-conversion changes on a real device, especially in the **Facebook in-app browser** (common traffic source).

**Test URL pattern:** `/biler/{slug}` on staging or production.

## Setup

- [ ] Use a published listing with dealer phone and WhatsApp configured
- [ ] Use a second listing marked **sold** (not draft preview)
- [ ] Test on a phone (iOS Safari + Android Chrome) and in FB in-app browser if possible
- [ ] Viewport width &lt; 1024px (mobile/tablet)

## Sticky bar (active listing)

- [ ] Sticky bar is visible at bottom without a white panel behind buttons
- [ ] **Call dealer** button has visible border/contrast (not white-on-white)
- [ ] **Send enquiry** button uses primary fill styling
- [ ] No price row in the sticky bar
- [ ] Tapping **Call** reveals phone / opens dialer and does not error
- [ ] Tapping **Send enquiry** opens the unified enquiry dialog

## Floating WhatsApp button

- [ ] Green circular WhatsApp button floats bottom-right (chatbot style)
- [ ] On mobile, FAB sits above the sticky CTA bar and does not overlap it
- [ ] On desktop, FAB sits in the bottom-right corner
- [ ] Tapping **WhatsApp** opens chat (or prompts appropriately)

## Contact Actions (inline, below price on mobile)

- [ ] Contact Actions section appears **immediately after price**, before finance calculator
- [ ] Only **Call** + **Send enquiry** are visible (no separate Exchange / Test drive / Price negotiation buttons)
- [ ] Button styles match sticky bar (high contrast)

## Unified enquiry dialog

- [ ] Dialog opens with **Enquiry** radio selected by default
- [ ] Radio options: Enquiry, Test drive, Exchange, Price negotiation
- [ ] Switching type updates title/description and shows correct fields
- [ ] **Exchange** shows licence plate, kilometres, expected price fields
- [ ] On narrow screens (&lt; 640px): vehicle info card hidden; name, email, phone visible; message field hidden for enquiry/test-drive
- [ ] Submitting enquiry without message succeeds (default message applied)
- [ ] Form submits successfully for each type (or shows validation errors as expected)

## Sold listing fallback

- [ ] Sold listing shows **Browse similar cars** in sticky bar (not Call/Enquiry)
- [ ] Button scrolls to related vehicles or goes to vehicle index
- [ ] Contact Actions partial is not shown when contact is blocked

## Performance / gallery

- [ ] Gallery still opens lightbox on first image tap
- [ ] No console errors related to Embla or GLightbox
- [ ] Page loads without blocking on gallery scripts (`defer` on CDN scripts)

## Tracking (optional)

- [ ] `cta_click` fires for phone, WhatsApp, enquiry sticky/inline buttons
- [ ] `form_open` fires when enquiry dialog opens
- [ ] Radio type change updates funnel `form` meta if analytics debugger is available

## Sign-off

| Tester | Date | Device / browser | Pass |
|--------|------|------------------|------|
|        |      |                  |      |
