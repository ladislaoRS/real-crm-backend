<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;
    protected User $user;
    protected Organization $organization;
    protected $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an account first (Pingcrm specific)
        $this->account = \App\Models\Account::create([
            'name' => 'Test Account',
        ]);

        // Create a test user with the account
        $this->user = User::factory()->create([
            'account_id' => $this->account->id,
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'owner' => true, // Make the user an owner of the account
        ]);

        // Create a test organization
        $this->organization = Organization::factory()->create([
            'account_id' => $this->account->id,
            'name' => 'Acme Inc.',
        ]);

        // Get a real token for the user
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $this->token = $response->json('token');
    }

    public function test_can_get_contacts_list()
    {
        // 1. Check that contacts list is empty initially
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/contacts');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        // Create some contacts to list
        Contact::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'organization_id' => $this->organization->id,
        ]);

        // Make the API request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/contacts');

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the response structure
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'city',
                    'organization',
                ]
            ],
            'links',
            'meta'
        ]);

        // Assert we got the right number of contacts
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_contact()
    {
        // Authenticate using token
        Sanctum::actingAs($this->user);

        // Create a test contact
        $contact = Contact::factory()->create([
            'account_id' => $this->account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'organization_id' => $this->organization->id,
        ]);

        // Make the API request
        $response = $this->getJson('/api/contacts/' . $contact->id);

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the response structure and content
        $response->assertJson([
            'data' => [
                'id' => $contact->id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'name' => 'John Doe',
                'organization' => [
                    'id' => $this->organization->id,
                    'name' => 'Acme Inc.',
                ],
            ]
        ]);
    }

    public function test_can_create_contact()
    {
        // 2. Create a new contact
        $contactData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '555-987-6543',
            'address' => '456 Oak Ave',
            'city' => 'Chicago',
            'region' => 'IL',
            'country' => 'US',
            'postal_code' => '60007',
            'organization_id' => $this->organization->id,
            'account_id' => $this->account->id, // Explicitly include the account_id
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/contacts', $contactData);

        // Assert the response is successful
        $response->assertStatus(201);

        // Assert the response contains the new contact
        $response->assertJson([
            'data' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
            ]
        ]);

        // Assert the contact was created in the database
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'account_id' => $this->account->id,
        ]);
    }

    public function test_can_update_contact()
    {
        // Create a contact to update
        $contact = Contact::factory()->create([
            'account_id' => $this->account->id,
            'organization_id' => $this->organization->id,
        ]);

        // 4. Update the contact
        $updateData = [
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
            'email' => 'robert.johnson@example.com',
            'phone' => '555-987-6543',
            'address' => '456 Oak Ave',
            'city' => 'Chicago',
            'region' => 'IL',
            'country' => 'US',
            'postal_code' => '60007',
            'organization_id' => $this->organization->id,
            'account_id' => $this->account->id, // Explicitly include the account_id
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/contacts/' . $contact->id, $updateData);

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the response contains the updated contact
        $response->assertJson([
            'data' => [
                'id' => $contact->id,
                'first_name' => 'Robert',
                'last_name' => 'Johnson',
                'email' => 'robert.johnson@example.com',
            ]
        ]);

        // Assert the contact was updated in the database
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
            'email' => 'robert.johnson@example.com',
            'account_id' => $this->account->id,
        ]);
    }

    public function test_can_delete_contact()
    {
        // Create a contact to delete
        $contact = Contact::factory()->create([
            'account_id' => $this->account->id,
            'organization_id' => $this->organization->id,
        ]);

        $contactId = $contact->id;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/contacts/' . $contactId);

        // Assert the response is successful
        $response->assertStatus(204);

        // Assert the contact was soft deleted
        $this->assertSoftDeleted('contacts', [
            'id' => $contactId,
        ]);
    }

    public function test_can_restore_contact()
    {
        // Create a contact and soft delete it
        $contact = Contact::factory()->create([
            'account_id' => $this->account->id,
            'organization_id' => $this->organization->id,
        ]);

        $contactId = $contact->id;
        $contact->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/contacts/' . $contactId . '/restore');

        // Assert the response is successful
        $response->assertStatus(204);

        // Assert the contact was restored
        $this->assertDatabaseHas('contacts', [
            'id' => $contactId,
            'deleted_at' => null,
            'account_id' => $this->account->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_contacts()
    {
        // Attempt to access contacts without authentication
        $response = $this->getJson('/api/contacts');

        // Assert the request is rejected with 401 Unauthorized
        $response->assertStatus(401);
    }

    public function test_validation_errors_when_creating_contact()
    {
        // Prepare invalid contact data (missing required fields)
        $invalidData = [
            'email' => 'not-an-email',
            'organization_id' => 999, // Non-existent organization
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/contacts', $invalidData);

        // Assert the response indicates validation errors
        $response->assertStatus(422);

        // Assert the response contains validation errors for the required fields
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'organization_id']);
    }

    public function test_can_filter_contacts_by_search()
    {
        // Create test contacts
        Contact::factory()->create([
            'account_id' => $this->account->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'organization_id' => $this->organization->id,
        ]);

        Contact::factory()->create([
            'account_id' => $this->account->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'organization_id' => $this->organization->id,
        ]);

        // Make the API request with search parameter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/contacts?search=John');

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert we only get the matching contact
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'John Smith');
    }

    public function test_can_filter_contacts_by_trashed()
    {
        // Create test contacts
        $contact1 = Contact::factory()->create([
            'account_id' => $this->account->id,
            'organization_id' => $this->organization->id,
        ]);

        $contact2 = Contact::factory()->create([
            'account_id' => $this->account->id,
            'organization_id' => $this->organization->id,
        ]);

        // Soft delete one contact
        $contact2->delete();

        // Get only trashed contacts
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/contacts?trashed=only');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $contact2->id);

        // Get contacts with trashed
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/contacts?trashed=with');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}
