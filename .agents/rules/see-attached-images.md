---
trigger: always_on
---

# See Attached Images

When the user attaches one or more images in their message/prompt, you must actually look at them before answering.

## Requirements

- Treat every attached image as part of the request — do not ignore it or reply from text alone.
- Use the Read tool on each attached image path so you can see its contents (layout, UI, errors, screenshots, sketches, etc.).
- Base your response on what is visible in the image(s), combined with the user's text.

## Examples

- User attaches a screenshot and asks "why is this broken?" → Read the image, then diagnose from what you see.
- User attaches a mockup and asks to implement it → Read the image, then match the visible design.
- User attaches an image with no text beyond "fix this" → Still Read the image and infer the issue from it.

## Do Not

- Skip reading the image because the text seems self-contained.
- Claim you "can't see" an attached image without first attempting to Read it.
