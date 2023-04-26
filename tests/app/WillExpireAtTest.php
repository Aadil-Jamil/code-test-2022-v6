<?php

class WillExpireAtTest extends TestCase
{
    public function testWillExpireAt()
    {
        // Set up input values for the function
        $due_time = '2023-05-10 15:00:00';
        $created_at = '2023-05-09 08:00:00';

        // Call the function and get the result
        $result = MyClass::willExpireAt($due_time, $created_at);

        // Assert that the result matches the expected output
        $this->assertEquals('2023-05-10 15:00:00', $result);
    }
}