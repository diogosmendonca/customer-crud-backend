<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Customer;
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
     * Tests the data input first name validation for inserting a customer.
     */
    public function test_create_customer_input_validation_first_name(){
        
        //create invalid customer data
        $customer_empty_first_name = Customer::factory()->make(["first_name" => "",]);
        $customer_empty_too_long = Customer::factory()->make(["first_name" => str_repeat("A", 256),]);
        
        //prepare input and expected messages
        $inputs = [$customer_empty_first_name, $customer_empty_too_long];
        $expected_msgs = ["The first name field is required.", "The first name must not be greater than 255 characters."];

        //testing for each input
        foreach($inputs as $i => $input) {
            //post the customer data to api
            $response = $this->postJson('/api/customers', $input->toArray());

            //check the status code
            $response->assertStatus(422);
            //check the validation msg
            $response->assertJson(
                [
                    "message" => "The given data was invalid.",
                    "errors" => [
                        "first_name" => [
                            $expected_msgs[$i]
                        ]
                    ]
                ]
            );
        }
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




}
