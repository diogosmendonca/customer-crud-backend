<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Routing\Middleware\ThrottleRequests;

class CustomerApiTest extends TestCase
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
     * Tests get of all customers.
     */
    public function test_get_customers_list()
    {
        $customers = Customer::factory(2)->create();

        $response = $this->get('/api/customers');
        $response->assertStatus(200);
        $response->assertJson($customers->toArray());
    }

    /**
     * Tests get of all customers with locations.
     */
    public function test_get_customers_list_with_locations()
    {
        $locations = Location::factory(2)->create();
        $customers = Customer::all();

        $response = $this->get('/api/customers');
        $response->assertStatus(200);
        $response->assertJson($customers->toArray());
        $response->assertJsonFragment($locations[0]->toArray());
        $response->assertJsonFragment($locations[1]->toArray());
    }

    /**
     * Tests get one customer.
     */
    public function test_get_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->get('/api/customers/'. $customer->id);
        $response->assertStatus(200);
        $response->assertJson($customer->toArray());
    }

    /**
     * Tests get one customer with locations.
     */
    public function test_get_customer_with_locations()
    {
        $customer = Customer::factory()->create();
        $locations = Location::factory(2)->make();
        $locations[0]->customer_id = $customer->id;
        $locations[0]->save();
        $locations[1]->customer_id = $customer->id;
        $locations[1]->save();

        $response = $this->get('/api/customers/'. $customer->id);
        $response->assertStatus(200);
        $response->assertJson($customer->toArray());
        $response->assertJsonFragment($locations[0]->toArray());
        $response->assertJsonFragment($locations[1]->toArray());
    }


    /**
     * Tests get a customer that do not exists.
     */
    public function test_get_customer_not_exists()
    {
        $response = $this->get('/api/customers/1');
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Record not found.'
        ]);
    }

    /**
     * Tests insert a valid customer.
     */
    public function test_create_valid_customer()
    {
        //create customer data
        $customer = Customer::factory()->make()->toArray();

        //post the customer data to api
        $response = $this->postJson('/api/customers', $customer);

        //check the status code
        $response->assertStatus(201);

        //data returned must be the same as submitted
        $response->assertJson($customer);

        //Api must return an integer id
        $json_response = $response->decodeResponseJson();
        $this->assertArrayHasKey("id", $json_response);
        $this->assertIsInt($json_response["id"]);

        //The data posted with the id returned must be present in the database
        $customer["id"] = $json_response["id"];
        $this->assertDatabaseHas('customers', $customer);
    }


    /**
     * Tests empty data validation for inserting a customer.
     */
    public function test_create_customer_input_validation_empty(){
        
        //create invalid customer data
        $customer_empty = Customer::factory()->make([
                "first_name" => "", 
                "last_name" => "", 
                "email" => "", 
                "phone" => "",]);
        
        //post the customer data to api
        $response = $this->postJson('/api/customers', $customer_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "first_name" => ["The first name field is required."],
                    "last_name" => ["The last name field is required."],
                    "email" => ["The email field is required."],
                    "phone" => ["The phone field is required."],
                ]
            ]
        );
    }
    

    /**
     * Tests too long data validation for inserting a customer.
     */
    public function test_create_customer_input_validation_too_long(){
        
        //create invalid customer data
        $customer_empty = Customer::factory()->make([
                "first_name" => str_repeat("A", 256), 
                "last_name" => str_repeat("A", 256), 
                "email" => str_repeat("A", 250) . "@teste.com", 
                "phone" => str_repeat("1", 31),]);
        
        //post the customer data to api
        $response = $this->postJson('/api/customers', $customer_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "first_name" => ["The first name must not be greater than 255 characters."],
                    "last_name" => ["The last name must not be greater than 255 characters."],
                    "email" => ["The email must not be greater than 255 characters."],
                    "phone" => ["The phone must not be greater than 30 characters."],
                ]
            ]
        );
    }

    /**
     * Tests invalid format data validation for inserting a customer.
     */
    public function test_create_customer_input_validation_invalid_format(){
        
        //create invalid customer data
        $customer_empty = Customer::factory()->make([
                "email" => "aaaateste", 
                "phone" => "letters",]);
        
        //post the customer data to api
        $response = $this->postJson('/api/customers', $customer_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "email" => ["The email must be a valid email address."],
                    "phone" => ["The phone format is invalid."],
                ]
            ]
        );
    }


    /**
     * Tests insert many valid customers for checking if data diversity is accepted by the API.
     */
    public function test_create_customer_data_diversity(){
        
        //create customers data
        $customers = Customer::factory(100)->make()->toArray();
        
        //insert each customer created
        foreach ($customers as &$customer) {
            //post the customer data to api
            $response = $this->postJson('/api/customers', $customer);
            //check the status code
            $response->assertStatus(201);
        }

    }

    /**
     * Tests update a valid customer.
     */
    public function test_update_valid_customer()
    {
        //create customer data
        $customer_created = Customer::factory()->create();
        $customer_updated = Customer::factory()->make();

        //post the customer data to api
        $response = $this->putJson('/api/customers/' . $customer_created->id, $customer_updated->toArray());

        //check the status code
        $response->assertStatus(200);

        //add the id attribute to updated data
        $customer_updated->id = $customer_created->id;

        //data returned must be the same as submitted
        $response->assertJson($customer_updated->toArray());

        $this->assertDatabaseHas('customers', $customer_updated->toArray());
        $this->assertDatabaseMissing('customers', $customer_created->toArray());
    }

    /**
     * Tests update a customer that do not exists.
     */
    public function test_update_customer_not_exists()
    {
        $response = $this->putJson('/api/customers/1');
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Record not found.'
        ]);
    }

        /**
     * Tests empty data validation for updating a customer.
     */
    public function test_update_customer_input_validation_empty(){
        
        $customer_created = Customer::factory()->create();
        //create invalid customer data
        $customer_empty = Customer::factory()->make([
                "first_name" => "", 
                "last_name" => "", 
                "email" => "", 
                "phone" => "",]);
        
        //post the customer data to api
        $response = $this->putJson('/api/customers/' . $customer_created->id, $customer_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "first_name" => ["The first name field is required."],
                    "last_name" => ["The last name field is required."],
                    "email" => ["The email field is required."],
                    "phone" => ["The phone field is required."],
                ]
            ]
        );
    }
    

    /**
     * Tests too long data validation for updating a customer.
     */
    public function test_update_customer_input_validation_too_long(){
        
        $customer_created = Customer::factory()->create();
        //create invalid customer data
        $customer_empty = Customer::factory()->make([
                "first_name" => str_repeat("A", 256), 
                "last_name" => str_repeat("A", 256), 
                "email" => str_repeat("A", 250) . "@teste.com", 
                "phone" => str_repeat("1", 31),]);
        
        //post the customer data to api
        $response = $this->putJson('/api/customers/' . $customer_created->id, $customer_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "first_name" => ["The first name must not be greater than 255 characters."],
                    "last_name" => ["The last name must not be greater than 255 characters."],
                    "email" => ["The email must not be greater than 255 characters."],
                    "phone" => ["The phone must not be greater than 30 characters."],
                ]
            ]
        );
    }

    /**
     * Tests invalid format data validation for updating a customer.
     */
    public function test_update_customer_input_validation_invalid_format(){
        
        $customer_created = Customer::factory()->create();
        //create invalid customer data
        $customer_empty = Customer::factory()->make([
                "email" => "aaaateste", 
                "phone" => "letters",]);
        
        //post the customer data to api
        $response = $this->putJson('/api/customers/' . $customer_created->id, $customer_empty->toArray());

        //check the status code
        $response->assertStatus(422);
        //check the validation msg
        $response->assertJson(
            [
                "message" => "The given data was invalid.",
                "errors" => [
                    "email" => ["The email must be a valid email address."],
                    "phone" => ["The phone format is invalid."],
                ]
            ]
        );
    }



    /**
     * Tests delete a valid customer.
     */
    public function test_delete_valid_customer()
    {
        //create customer data
        $customer = Customer::factory()->create();

        //post the customer data to api
        $response = $this->deleteJson('/api/customers/' . $customer->id);

        //check the status code
        $response->assertStatus(204);

        $this->assertDatabaseMissing('customers', $customer->toArray());
    }

    /**
     * Tests delete a customer that do not exists.
     */
    public function test_delete_customer_not_exists()
    {
        $response = $this->deleteJson('/api/customers/1');
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Record not found.'
        ]);
    }

}
