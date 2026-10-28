Kate & Rate Careers — 0.2.0

INSTALL
1. Upload and activate this ZIP at Plugins > Add New > Upload Plugin.
2. Create a Careers page with slug /careers/. In Elementor Page Settings choose Elementor Full Width and Hide Title: Yes. Put [kate_rate_careers] in a Shortcode widget inside one full-width container. The plugin aligns the Careers listing to the viewport on phones, tablets and desktop.
3. Visit Careers > Page settings to edit the page headline, introduction and default application email. Verify the email before launch.
   Select the published Careers page and edit the standalone button label there.
4. Add /careers/ to the offcanvas navigation and footer if desired. Individual role pages use /careers/role/role-slug/ and the site's active header and footer. If a role URL returns 404, save Settings > Permalinks once to refresh WordPress rewrite rules.

CLIENT WORKFLOW
- Careers > Add role opens a dedicated form. Enter a title, card summary, location, overview, responsibilities and requirements. Optional fields include department, work arrangement, benefits, closing date and application instructions.
- Choose one application method for each role: applicant's email app, external website, or a form on the role page. Existing roles retain their earlier destination. Set a role-specific application email or use the confirmed Page settings default for email-app and website-form applications.
- The website form collects name, email, optional phone and introduction, and one PDF, DOC or DOCX CV (up to 5 MB). It emails the information and the CV as an attachment to the selected application email. The plugin does not save the CV in the Media Library or create a WordPress application record. The site's mail delivery must be configured and tested; wp_mail returning success is not proof that the message arrived in the inbox.
- Save draft to work privately. Publish role when ready. The Careers page shows each current role as a card and links to a complete individual role page.
- An expired role automatically disappears from the list; its detail page says applications are closed and removes the Apply button. Use the Close action in Careers > All roles to close one earlier, or change its date and publish it again.
- When there are no open roles, the page shows a clear no-open-positions message rather than invented vacancies.

VIEW ALL CAREERS BUTTON
- Place [kate_rate_careers_link] in an Elementor Shortcode widget on any page where a button should lead to the full Careers page. This shortcode outputs only the button, not the job listing or page hero.
- Optional per-placement wording: [kate_rate_careers_link label="Explore careers"]. The default wording is editable under Careers > Page settings.
- The button works even when there are no open roles; the Careers page explains that there are no current openings.

TEST
- Publish a genuine sample role, inspect its list card and individual URL on phone and desktop, check the application destination, then close or draft the role.
- After upgrading, clear site and browser caches. If a second "Career" title appears beside the plugin hero, set Hide Title: Yes in Elementor and remove any duplicate heading widget on that page.
- If an external application page is used, check it is controlled by the business or its hiring provider. Keep the Privacy Policy aligned with any applications received by email or a third party.
- For website-form roles, submit one test application with a small non-sensitive sample CV. Confirm the message and attachment arrive at the role-specific or default inbox, and check both successful and failed submissions. The form uses a nonce, a honeypot, a per-applicant cooldown and file size/type checks. DOCX uploads require PHP's ZipArchive extension. Do not use a real applicant's data for testing.

PRIVACY POLICY INTEGRATION
- The job Apply action links visitors to /privacy-policy/. Update that page to explain that applicant details and CVs are emailed to the business for recruitment, who can access the inbox, the retention period and deletion/contact process before enabling the website form for real applicants.
