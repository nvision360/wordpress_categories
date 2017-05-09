# Fetch External Categories Into Wordpress

This plugin fetches categories from external API. It has been scheduled to look for any updates after every 30 minutes


## Deployment

1. Upload and extract plugin zip folder
2. Open config.php and add API link under API_ENDPOINT
3. Add taxonomy under APP_TAXONOMY(default is set to 'categories')
4. Add schedule interval under APP_INTERVAL(default value is 1800 seconds, 30 minutes)
5. Activate the plugin and goto Settings->Update Categories link

## Pending Work

*If categories are deleted on the API, it will not reflect in WP. I left it because it was not mention in the task

*Disable adding of categories

### My Thoughts

It was a little bit challenging task. Overall I really enjoyed working on it and I am sure little bit more tweaking to the plugin could be useful for future projects
related to importing taxonomies and managing directly outside using a single system

### Time Taken

It took 2.5 hours in total including testing and uploading to git


