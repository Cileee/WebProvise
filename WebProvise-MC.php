<?php

class Travel
{
    /**
     * Retrieves travel data from a mock API endpoint.
     *
     * @return array An array containing travel data.
     * @throws Exception If there is an error in making the API request or decoding the JSON response.
     */
    public static function getData(): array
    {
        $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';
        return self::makeRequest($url);
    }

    /**
     * Sends a cURL request to the specified URL and returns the decoded JSON response or false on failure.
     *
     * @param string $url The URL to make the cURL request to.
     * @return array|false An array containing the decoded JSON response or false on failure.
     * @throws Exception If there is an error in making the cURL request or decoding the JSON response.
     */
    private static function makeRequest(string $url): array|false
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception('Error: ' . curl_error($curl));
        }

        curl_close($curl);

        $decodedResponse = json_decode($response, true);

        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding JSON response');
        }

        return $decodedResponse;
    }
}

class Company
{
    /**
     * Retrieves company data from a mock API endpoint.
     *
     * @return array An array containing company data.
     * @throws Exception If there is an error in making the API request or decoding the JSON response.
     */
    private static function getData(): array
    {
        $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
        return self::makeRequest($url);
    }

    /**
     * Builds a hierarchical tree structure of companies based on their parent-child relationships.
     *
     * @param array  $indexedCompanies An array of companies indexed by their IDs.
     * @param string $parentId          The ID of the parent company for which to build the tree.
     * @return array An array representing the hierarchical tree structure of companies.
     */
    public static function buildTree(array $indexedCompanies, string $parentId): array
    {
        $tree = [];

        foreach ($indexedCompanies as $companyId => $company) {
            if ($company['parentId'] == $parentId) {
                $company['children'] = self::buildTree($indexedCompanies, $companyId);
                if ($company['children']) {
                    $company['cost'] += array_sum(array_column($company['children'], 'cost'));
                }

                $tree[] = $company;
            }
        }

        return $tree;
    }

    /**
     * Builds an indexed array of companies with additional information, including the total cost calculated from associated travel data.
     *
     * @return array An array of companies indexed by their IDs, with additional information including the total cost.
     * @throws Exception If there is an error in retrieving company or travel data.
     */
    public static function buildIndexedCompanies(): array
    {
        try {
            $companies = self::getData();
            $travels = Travel::getData();

            $indexedCompanies = [];

            foreach ($companies as $company) {
                $company['cost'] = 0;
                $indexedCompanies[$company['id']] = $company;
            }

            foreach ($travels as $travel) {
                if (isset($indexedCompanies[$travel['companyId']])) {
                    $indexedCompanies[$travel['companyId']]['cost'] += $travel['price'];
                }
            }

            return $indexedCompanies;
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    /**
     * Sends a cURL request to the specified URL and returns the decoded JSON response or false on failure.
     *
     * @param string $url The URL to make the cURL request to.
     * @return array|false An array containing the decoded JSON response or false on failure.
     * @throws Exception If there is an error in making the cURL request or decoding the JSON response.
     */
    private static function makeRequest(string $url): array|false
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception('Error: ' . curl_error($curl));
        }

        curl_close($curl);

        $decodedResponse = json_decode($response, true);

        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding JSON response');
        }

        return $decodedResponse;
    }
}

class TestScript
{
    public function execute(): void
    {
        $start = microtime(true);

        $topLevelParentId = '0';

        $indexedCompanies = Company::buildIndexedCompanies();
        $result = Company::buildTree($indexedCompanies, $topLevelParentId);

        echo json_encode($result, JSON_PRETTY_PRINT);

        echo 'Total time: ' . (microtime(true) - $start);
    }
}

(new TestScript())->execute();
