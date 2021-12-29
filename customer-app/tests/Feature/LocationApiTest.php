<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Routing\Middleware\ThrottleRequests;

class LocationApiTest extends TestCase
{

    use RefreshDatabase;


    public function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );
    }

    
    /**
     * Tests get of all locations.
     */
    public function test_get_location_list()
    {
        $locations = Location::factory(2)->create();

        $response = $this->get('/api/locations');
        $response->assertStatus(200);
        $response->assertJson($locations->toArray());
    }


     /**
     * Tests get one location.
     */
    public function test_get_location()
    {
        $location = Location::factory()->create();

        $response = $this->get('/api/locations/'. $location->id);
        $response->assertStatus(200);
        $response->assertJson($location->toArray());
    }

    

    /**
     * Tests get a location that does not exists.
     */
    public function test_get_location_not_exists()
    {
        $response = $this->get('/api/locations/1');
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Record not found.'
        ]);
    }

    /**
     * Tests insert a valid location.
     */
    public function test_create_valid_location()
    {
        //create location data
        $location = Location::factory()->make()->toArray();

        //post the location data to api
        $response = $this->postJson('/api/locations', $location);

        //check the status code
        $response->assertStatus(201);

        //data returned must be the same as submitted
        $response->assertJson($location);

        //Api must return an integer id
        $json_response = $response->decodeResponseJson();
        $this->assertArrayHasKey("id", $json_response);
        $this->assertIsInt($json_response["id"]);

        //The data posted with the id returned must be present in the database
        $location["id"] = $json_response["id"];
        $this->assertDatabaseHas('locations', $location);
    }


    /**
     * Tests empty data validation for inserting a location.
     */
    public function test_create_location_input_validation_empty(){
        
        //create invalid customer data
        $location_empty = Location::factory()->make([
                "address" => "", 
                "city" => "", 
                "state" => "", 
                "zip" => "",
                "customer_id" => "",]);
        
        //post the location data to api
        $response = $this->postJson('/api/locations', $location_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "address" => ["The address field is required."],
                    "city" => ["The city field is required."],
                    "state" => ["The state field is required."],
                    "zip" => ["The zip field is required."],
                    "customer_id" => ["The customer id field is required."],
                ]
            ]
        );
    }


    /**
     * Tests empty data validation for inserting a location.
     */
    public function test_create_location_input_validation_too_long(){
        
        //create invalid customer data
        $location_empty = Location::factory()->make([
                "address" => str_repeat("A", 256), 
                "city" => str_repeat("A", 256), 
                "state" => str_repeat("A", 256), 
                "zip" => str_repeat("1", 31), 
                "customer_id" => str_repeat("1", 256),]);
        
        //post the location data to api
        $response = $this->postJson('/api/locations', $location_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "address" => ["The address must not be greater than 255 characters."],
                    "city" => ["The city must not be greater than 255 characters."],
                    "state" => ["The state must not be greater than 255 characters."],
                    "zip" => ["The zip must not be greater than 30 characters."],
                    "customer_id" => ["The selected customer id is invalid."],
                ]
            ]
        );
    }

    /**
     * Tests invalid data validation for inserting a location.
     */
    public function test_create_location_input_validation_invalid(){
        
        //create invalid customer data
        $location_empty = Location::factory()->make([
                "zip" => str_repeat("AA", 10), 
                "customer_id" => "1",]);
        
        //post the location data to api
        $response = $this->postJson('/api/locations', $location_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "zip" => ["The zip format is invalid."],
                    "customer_id" => ["The selected customer id is invalid."],
                ]
            ]
        );
    }


     /**
     * Tests insert many valid locations for checking if data diversity is accepted by the API.
     */
    public function test_create_location_data_diversity(){
        
        //create location data
        $locations = Location::factory(100)->make()->toArray();
        
        //insert each customer created
        foreach ($locations as &$location) {
            //post the customer data to api
            $response = $this->postJson('/api/locations', $location);
            //check the status code
            $response->assertStatus(201);
        }
    }


     /**
     * Tests update a valid location.
     */
    public function test_update_valid_location()
    {
        //create location data
        $location_created = Location::factory()->create();
        $location_updated = Location::factory()->make();

        //post the location data to api
        $response = $this->putJson('/api/locations/' . $location_created->id, $location_updated->toArray());

        //check the status code
        $response->assertStatus(200);

        //add the id attribute to updated data
        $location_updated->id = $location_created->id;

        //data returned must be the same as submitted
        $response->assertJson($location_updated->toArray());
        
        $this->assertDatabaseHas('locations', $location_updated->toArray());
        $this->assertDatabaseMissing('locations', $location_created->toArray());
    }

    /**
     * Tests update a location that do not exists.
     */
    public function test_update_location_not_exists()
    {
        $response = $this->putJson('/api/locations/1');
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Record not found.'
        ]);
    }

    
    /**
     * Tests empty data validation for updating a location.
     */
    public function test_update_location_input_validation_empty(){
        
        $location_created = Location::factory()->create();
        //create invalid customer data
        $location_empty = Location::factory()->make([
                "address" => "", 
                "city" => "", 
                "state" => "", 
                "zip" => "",
                "customer_id" => "",]);
        
        //post the location data to api
        $response = $this->putJson('/api/locations/' . $location_created->id, $location_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "address" => ["The address field is required."],
                    "city" => ["The city field is required."],
                    "state" => ["The state field is required."],
                    "zip" => ["The zip field is required."],
                    "customer_id" => ["The customer id field is required."],
                ]
            ]
        );
    }


    /**
     * Tests empty data validation for updating a location.
     */
    public function test_update_location_input_validation_too_long(){
        
        $location_created = Location::factory()->create();
        //create invalid customer data
        $location_empty = Location::factory()->make([
                "address" => str_repeat("A", 256), 
                "city" => str_repeat("A", 256), 
                "state" => str_repeat("A", 256), 
                "zip" => str_repeat("1", 31), 
                "customer_id" => str_repeat("1", 256),]);
        
        //post the location data to api
        $response = $this->putJson('/api/locations/' . $location_created->id, $location_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "address" => ["The address must not be greater than 255 characters."],
                    "city" => ["The city must not be greater than 255 characters."],
                    "state" => ["The state must not be greater than 255 characters."],
                    "zip" => ["The zip must not be greater than 30 characters."],
                    "customer_id" => ["The selected customer id is invalid."],
                ]
            ]
        );
    }


    
    /**
     * Tests invalid data validation for updating a location.
     */
    public function test_update_location_input_validation_invalid(){
        
        $location_created = Location::factory()->create();
        //create invalid customer data
        $location_empty = Location::factory()->make([
                "zip" => str_repeat("AA", 10), 
                "customer_id" => "1",]);
        
        //post the location data to api
        $response = $this->putJson('/api/locations/' . $location_created->id, $location_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "zip" => ["The zip format is invalid."],
                    "customer_id" => ["The selected customer id is invalid."],
                ]
            ]
        );
    }

    /**
     * Tests delete a valid location.
     */
    public function test_delete_valid_customer()
    {
        //create location data
        $location = Location::factory()->create();

        //post the location data to api
        $response = $this->deleteJson('/api/locations/' . $location->id);

        //check the status code
        $response->assertStatus(204);

        $this->assertDatabaseMissing('locations', $location->toArray());
    }

    /**
     * Tests delete a location that do not exists.
     */
    public function test_delete_location_not_exists()
    {
        $response = $this->deleteJson('/api/locations/1');
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Record not found.'
        ]);
    }

}
