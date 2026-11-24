# Release and rollback

1. Deploy additive code with Wannads/TheoremReach **disabled** by default.  
2. Kill switch: Admin → Ad providers → disable provider (preserves history).  
3. Do not disable TapResearch/AdSense to hide new bugs.  
4. Schema: no destructive migration in this expansion (config JSON + existing tables).  
5. Rollback = redeploy previous SHA; leave new callback rows intact.
