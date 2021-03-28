### Startup project
- Run *docker-compose up -d*
- Go to *http://localhost:8080*, should see Love, Benito in the website

### Challenge 2
- The logic here is running a scheduler every minute that check storage/worldcities.csv, 
if the file was updated, it will copy the asean countries data and create storage/cities.csv
- To run manually, can use the command: *docker exec -t {cron_container_name} php src/Jobs/scheduler.php*

### Challenge 3
- api to update city is: *PUT http://localhost:8080/cities/{city_id}*
- required params: ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population']
- example: 
```
curl --location --request PUT 'http://localhost:8082/cities/1' \
       --header 'Content-Type: application/json' \
       --data-raw '{
           "city": "hanoi34",
           "country": "Vietnam",
           "city_ascii": "123",
           "lat": "12",
           "lng": "23",
           "iso2": "US",
           "iso3": "abc",
           "admin_name": "admin",
           "capital": "hanoi",
           "population": "124"
       }'
```

### Test
- Run *composer install*
- Run *php vendor/bin/codecept run*

