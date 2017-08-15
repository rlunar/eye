<?php

use Eyewitness\Eye\App\Witness\Log;

class LogControllerTest extends TestCase
{
    protected $logMock;

    public function setUp()
    {
        parent::setUp();

        $this->logMock = Mockery::mock(Log::class);
        $this->app->instance(Log::class, $this->logMock);
    }

    public function test_log_controller_required_authentication()
    {
        $response = $this->call('GET', $this->api.'log');
        $this->assertEquals(json_encode(['error' => 'Unauthorized']), $response->getContent());
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_log_honours_config()
    {
        $this->app['config']->set('eyewitness.routes_log', false);

        $response = $this->call('GET', $this->api.'log'.$this->auth);
        $this->assertEquals(json_encode(['error' => 'The log route is disabled on the server']), $response->getContent());
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->call('GET', $this->api.'log/show'.$this->auth, ['filename' => 'test.log', 'count' => 3]);
        $this->assertEquals(json_encode(['error' => 'The log route is disabled on the server']), $response->getContent());
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function test_log_index_gets_log_files()
    {
        $this->logMock->shouldReceive('getLogFiles')
                      ->once()
                      ->andReturn(['log' => 'example']);

        $response = $this->call('GET', $this->api.'log'.$this->auth);

        $this->assertEquals(json_encode(['log_files' => ['log' => 'example']]), $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_log_show_handles_file_not_found()
    {
        $this->logMock->shouldReceive('readLogFile')->never();
        $this->logMock->shouldReceive('getLogFilenames')
                      ->once()
                      ->andReturn(['example']);

        $response = $this->call('GET', $this->api.'log/show'.$this->auth, ['filename' => 'test.log', 'count' => 3]);

        $this->assertEquals(json_encode(['error' => 'File not found']), $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_log_show()
    {
        $this->logMock->shouldReceive('getLogFilenames')
                      ->once()
                      ->andReturn(['test.log']);

        $this->logMock->shouldReceive('readLogFile')
                      ->with('test.log', 3, 0, null)
                      ->once()
                      ->andReturn(['log' => 'example']);

        $response = $this->call('GET', $this->api.'log/show'.$this->auth, ['filename' => 'test.log', 'count' => 3]);

        $this->assertEquals(json_encode(['log' => 'example']), $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

}
