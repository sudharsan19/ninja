@sla
Feature: SLA reports
	Warning: Assumes the time format is ISO-8601 (the default)

	Background:
		Given I have these hostgroups configured:
			| hostgroup_name | alias      |
			| LinuxServers   | HGALIAS-ls |
			| WindowsServers | HGALIAS-ws |
			| MixedGroup     | HGALIAS-mg |
			| EmptyGroup     | HGALIAS-eg |
		And I have these hosts:
			| host_name      | host_groups               | alias      |
			| linux-server1  | LinuxServers,MixedGroup   | HALIAS-ls1 |
			| linux-server2  | LinuxServers              | HALIAS-ls2 |
			| win-server1    | WindowsServers            | HALIAS-ws1 |
			| win-server2    | WindowsServers,MixedGroup | HALIAS-ws2 |
		And I have these servicegroups:
			| servicegroup_name |
			| pings             |
			| empty             |
		And I have these services:
			| service_description | host_name     | check_command   | notifications_enabled | active_checks_enabled | service_groups |
			| System Load         | linux-server1 | check_nrpe!load | 1                     | 1                     |                |
			| PING                | linux-server1   | check_ping    | 1                     | 0                     | pings          |
			| System Load         | linux-server2 | check_nrpe!load | 1                     | 1                     |                |
			| PING                | win-server1   | check_ping      | 1                     | 0                     | pings          |
			| PING                | win-server2   | check_ping      | 0                     | 1                     | pings          |
		And I have these report data entries:
			| timestamp           | event_type | flags | attrib | host_name     | service_description | state | hard | retry | downtime_depth | output |
			| 2013-01-01 12:00:00 |        100 |  NULL |   NULL |               |                     |     0 |    0 |     0 |           NULL | NULL                |
			| 2013-01-01 12:00:01 |        801 |  NULL |   NULL | win-server1   |                     |     0 |    1 |     1 |           NULL | OK - laa-laa        |
			| 2013-01-01 12:00:02 |        801 |  NULL |   NULL | linux-server1 |                     |     0 |    1 |     1 |           NULL | OK - Sven Melander  |
			| 2013-01-01 12:00:03 |        701 |  NULL |   NULL | win-server1   | PING                |     0 |    1 |     1 |           NULL | OK - po             |
			| 2013-01-01 12:00:03 |        701 |  NULL |   NULL | win-server1   | PING                |     1 |    0 |     1 |           NULL | ERROR - tinky-winky |

		And I have activated the configuration

	@configuration @asmonitor @reports
	Scenario: Generate report without objects
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "Please select what objects to base the report on"
		And I should see "Report Settings"

	@configuration @asmonitor @reports
	Scenario: Generate report on empty hostgroup
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "EmptyGroup" from "Available hostgroups"
		And I doubleclick "EmptyGroup" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "EmptyGroup"
		When I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "No objects could be found in your selected groups to base the report on"
		And I should see "Report Settings"

	@configuration @asmonitor @reports
	Scenario: Generate report on empty servicegroup
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Servicegroups" from "Report type"
		And I select "empty" from "Available servicegroups"
		And I doubleclick "empty" from "servicegroup_tmp[]"
		Then "Selected servicegroups" should have option "empty"
		When I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "No objects could be found in your selected groups to base the report on"
		And I should see "Report Settings"

	@configuration @asmonitor @reports
	Scenario: Generate report without SLA values
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "LinuxServers" from "Available hostgroups"
		And I doubleclick "LinuxServers" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "LinuxServers"
		When I click "Show report"
		Then I should see "Please enter at least one SLA value"
		And I should see "Report Settings"

	@configuration @asmonitor @reports
	Scenario: Generate single host report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Hosts" from "Report type"
		And I select "linux-server1" from "Available hosts"
		And I doubleclick "linux-server1" from "host_tmp[]"
		Then "Selected hosts" should have option "linux-server1"
		When I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for: linux-server1"
		And I shouldn't see "linux-server2"
		And I shouldn't see "win-server1"
		When I click "Show availability breakdown"
		Then I should see "Host details"
		And I should see "linux-server1"

	@configuration @asmonitor @reports
	Scenario: Generate multi host report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Hosts" from "Report type"
		And I select "linux-server1" from "Available hosts"
		And I doubleclick "linux-server1" from "host_tmp[]"
		And I select "win-server1" from "Available hosts"
		And I doubleclick "win-server1" from "host_tmp[]"
		Then "Selected hosts" should have option "linux-server1"
		And "Selected hosts" should have option "win-server1"
		When I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for custom group"
		And I should see "Group members"
		And I should see "linux-server1"
		And I should see "win-server1"
		And I shouldn't see "linux-server2"
		And I shouldn't see "win-server2"
		And I should see "9.000 %"
		When I click "Show availability breakdown"
		Then I should see "Host state breakdown"
		And I should see "linux-server1"
		And I should see "win-server1"

	@configuration @asmonitor @reports
	Scenario: Generate single service report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Services" from "Report type"
		And I select "linux-server1;PING" from "Available services"
		And I doubleclick "linux-server1;PING" from "service_tmp[]"
		Then "Selected services" should have option "linux-server1;PING"
		When I enter "9.1" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for: linux-server1;PING"
		And I shouldn't see "System Load"
		And I shouldn't see "win-server"
		And I should see "9.100 %"
		When I click "Show availability breakdown"
		Then I should see "Service details for PING on host linux-server1"

	@configuration @asmonitor @reports
	Scenario: Generate multi service on same host report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Services" from "Report type"
		And I select "linux-server1;PING" from "Available services"
		And I doubleclick "linux-server1;PING" from "service_tmp[]"
		And I select "linux-server1;System Load" from "Available services"
		And I doubleclick "linux-server1;System Load" from "service_tmp[]"
		Then "Selected services" should have option "linux-server1;PING"
		And "Selected services" should have option "linux-server1;System Load"
		When I enter "9,1" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for custom group"
		And I should see "Group members"
		And I should see "linux-server1;PING"
		And I should see "linux-server1;System Load"
		And I shouldn't see "linux-server2"
		And I shouldn't see "win-server1"
		And I should see "9.100 %"
		When I click "Show availability breakdown"
		Then I should see "Service state breakdown"
		And I should see "Services on host: linux-server1"
		And I should see "PING"
		And I should see "System Load"

	@configuration @asmonitor @reports
	Scenario: Generate multi service on different host report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Services" from "Report type"
		And I select "linux-server1;PING" from "Available services"
		And I doubleclick "linux-server1;PING" from "service_tmp[]"
		And I select "linux-server2;System Load" from "Available services"
		And I doubleclick "linux-server2;System Load" from "service_tmp[]"
		Then "Selected services" should have option "linux-server1;PING"
		And "Selected services" should have option "linux-server2;System Load"
		When I enter "9.99" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for custom group"
		And I should see "Group members"
		And I should see "linux-server1;PING"
		And I should see "linux-server2;System Load"
		And I shouldn't see "linux-server2;PING"
		And I shouldn't see "linux-server1;System Load"
		And I shouldn't see "win-server1"
		And I should see "9.990 %"
		When I click "Show availability breakdown"
		Then I should see "Service state breakdown"
		And I should see "Services on host: linux-server1"
		And I should see "Services on host: linux-server2"
		And I should see "PING"
		And I should see "System Load"

	@configuration @asmonitor @reports
	Scenario: Generate single hostgroup report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "LinuxServers" from "Available hostgroups"
		And I doubleclick "LinuxServers" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "LinuxServers"
		When I enter "9,99" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for: LinuxServers"
		And I should see "Group members"
		And I should see "linux-server1"
		And I should see "linux-server2"
		And I shouldn't see "win-server1"
		And I shouldn't see "win-server2"
		And I should see "9.990 %"
		When I click "Show availability breakdown"
		Then I should see "Hostgroup breakdown"
		And I should see "linux-server1"
		And I should see "linux-server2"

	@configuration @asmonitor @reports
	Scenario: Generate multi hostgroup report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "LinuxServers" from "Available hostgroups"
		And I doubleclick "LinuxServers" from "hostgroup_tmp[]"
		And I select "WindowsServers" from "Available hostgroups"
		And I doubleclick "WindowsServers" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "LinuxServers"
		And "Selected hostgroups" should have option "WindowsServers"
		When I enter "99.999" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for: LinuxServers"
		And I should see "SLA breakdown for: WindowsServers"
		And I should see "Group members"
		And I should see "linux-server1"
		And I should see "linux-server2"
		And I should see "win-server1"
		And I should see "win-server2"
		And I should see "99.999 %"
		# By pure chance (aka "first match"), this will be the top graph, aka the LinuxServers one
		When I click "Show availability breakdown"
		Then I should see "Hostgroup breakdown"
		And I should see "linux-server1"
		And I should see "linux-server2"

	@configuration @asmonitor @reports
	Scenario: Generate hostgroup report with overlapping members
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "LinuxServers" from "Available hostgroups"
		And I doubleclick "LinuxServers" from "hostgroup_tmp[]"
		And I select "MixedGroup" from "Available hostgroups"
		And I doubleclick "MixedGroup" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "LinuxServers"
		And "Selected hostgroups" should have option "MixedGroup"
		When I enter "99,999" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for: LinuxServers"
		And I should see "SLA breakdown for: MixedGroup"
		And I should see "Group members"
		And I should see "linux-server1"
		And I should see "linux-server2"
		And I shouldn't see "win-server1"
		And I should see "win-server2"
		And I should see "99.999 %"
		# By pure chance (aka "first match"), this will be the top graph, aka the LinuxServers one
		When I click "Show availability breakdown"
		Then I should see "Hostgroup breakdown"
		And I should see "linux-server1"
		And I should see "linux-server2"

	@configuration @asmonitor @reports
	Scenario: Generate single servicegroup report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Servicegroups" from "Report type"
		And I select "pings" from "Available servicegroups"
		And I doubleclick "pings" from "servicegroup_tmp[]"
		Then "Selected servicegroups" should have option "pings"
		When I enter "100" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for: pings"
		And I should see "Group members"
		And I should see "linux-server1;PING"
		And I should see "win-server1;PING"
		And I should see "win-server2;PING"
		And I shouldn't see "linux-server2"
		And I shouldn't see "System Load"
		And I should see "100 %"
		When I click "Show availability breakdown"
		Then I should see "Servicegroup breakdown"
		And I should see "Services on host: linux-server1"
		And I should see "Services on host: win-server1"
		And I should see "Services on host: win-server2"
		And I should see "PING"

	@configuration @asmonitor @reports
	Scenario: Generate multi servicegroup report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "Servicegroups" from "Report type"
		And I select "pings" from "Available servicegroups"
		And I doubleclick "pings" from "servicegroup_tmp[]"
		And I select "empty" from "Available servicegroups"
		And I doubleclick "empty" from "servicegroup_tmp[]"
		Then "Selected servicegroups" should have option "pings"
		And "Selected servicegroups" should have option "empty"
		When I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown for: pings"
		And I shouldn't see "SLA breakdown for: empty"
		And I should see "Group members"
		And I should see "linux-server1;PING"
		And I should see "win-server1;PING"
		And I should see "win-server2;PING"
		And I shouldn't see "linux-server2"
		And I shouldn't see "System Load"
		When I click "Show availability breakdown"
		Then I should see "Servicegroup breakdown"
		And I should see "Services on host: linux-server1"
		And I should see "Services on host: win-server1"
		And I should see "Services on host: win-server2"
		And I should see "PING"

	@configuration @asmonitor @reports
	Scenario: Generate report on custom report date
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "LinuxServers" from "Available hostgroups"
		And I doubleclick "LinuxServers" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "LinuxServers"
		When I select "Custom" from "Reporting period"
		And I select "2013" from "Start year"
		And I select "Jan" from "Start month"
		And I select "2013" from "End year"
		And I select "Mar" from "End month"
		Then "Jan" should be enabled
		And "Mar" should be enabled
		And "May" should be disabled
		And "Dec" should be disabled
		And I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		Then "Jan" should contain "9"
		And "Feb" should contain "9"
		And "Mar" should contain "9"
		When I click "Show report"
		Then I should see "SLA breakdown"
		And I should see "Reporting period: 2013-01-01 to 2013-03-31 - 24x7"

	@configuration @asmonitor @reports
	Scenario: Ensure correct timeperiod is carried over to avail
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		And I select "LinuxServers" from "Available hostgroups"
		And I doubleclick "LinuxServers" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "LinuxServers"
		When I select "Last 12 months" from "Reporting period"
		And I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I click "Show report"
		Then I should see "SLA breakdown"
		And I should see "Reporting period: Last 12 months"
		When I click "Show availability breakdown"
		Then I should see "Hostgroup breakdown"
		And I should see "LinuxServers"
		And I should see "linux-server1"
		And I should see "linux-server2"
		And I shouldn't see "win-server1"
		And I shouldn't see "win-server2"
		And I should see "Group availability (SLA)"
		And I should see "Reporting period: Last 12 months"

	@configuration @asmonitor @reports
	Scenario: Save report with misc options
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		Then I should see "Saved reports"
		And "Saved reports" shouldn't have option "saved test report"
		When I select "LinuxServers" from "Available hostgroups"
		And I doubleclick "LinuxServers" from "hostgroup_tmp[]"
		Then "Selected hostgroups" should have option "LinuxServers"
		# Toggle *everything*!
		When I select "Last year" from "Reporting period"
		And I enter "9" into "Jan"
		And I click "Click to propagate this value to all months"
		And I select "workhours" from "Report time period"
		And I check "Down"
		And I select "Average" from "SLA calculation method"
		And I select "Uptime, with difference" from "Count scheduled downtime as"
		And I select "Undetermined" from "Count program downtime as"
		And I check "Include soft states"
		And I check "Use alias"
		And I select "pink_n_fluffy" from "Skin"
		And I enter "This is a saved test report" into "Description"
		And I click "Show report"
		# I don't care where, but I want everything to be visible somehow
		Then I should see "Last year"
		And I should see "workhours"
		And I should see "Showing hosts in state: up, unreachable, pending"
		And I should see "Average"
		And I shouldn't see "SLA"
		And I should see "Uptime, with difference"
		And I shouldn't see "Counting program downtime"
		And I should see "Including soft states"
		And I should see "HALIAS-ls1"
		And I should see "HALIAS-ls2"
		And I should see "HGALIAS-ls"
		And I should see "This is a saved test report"
		And I should see "9.000 %"
		And Screenshot "foo1.png"
		When I click "Save report"
		And I enter "saved test report" into "report_name"
		And I click "Save report" inside "#save_report_form"
		Then I should see "Report was successfully saved"

	@configuration @asmonitor @reports
	Scenario: View saved report
		Given I am on the Host details page
		When I hover over the "Reporting" button
		And I click "SLA"
		Then I should see "Saved reports"
		And "Saved reports" should have option "saved test report"
		When I select "saved test report" from "Saved reports"
		Then "Selected hostgroups" should have option "LinuxServers"
		And Screenshot "foo2.png"
		And "Last year" should be selected from "Reporting period"
		And "workhours" should be selected from "Report time period"
		And "Down" should be checked
		And "Average" should be selected from "SLA calculation method"
		And "Uptime, with difference" should be selected from "Count scheduled downtime as"
		And "Undetermined" should be selected from "Count program downtime as"
		And "Include soft states" should be checked
		And "Use alias" should be checked
		And "pink_n_fluffy" should be selected from "Skin"
		And "Description" should contain "This is a saved test report"
		And "Jan" should contain "9"
		And "Feb" should contain "9"
		When I click "Show report"
		Then I should see "Last year"
		And I should see "workhours"
		And I should see "Showing hosts in state: up, unreachable, pending"
		And I should see "Average"
		And I shouldn't see "SLA"
		And I should see "Uptime, with difference"
		And I shouldn't see "Counting program downtime"
		And I should see "Including soft states"
		And I should see "HALIAS-ls1"
		And I should see "HALIAS-ls2"
		And I should see "HGALIAS-ls"
		And I should see "This is a saved test report"
		And I should see "9.000 %"

	@configuration @asmonitor @reports
	Scenario: Delete previously created report
		Given I am on the Host details page
		And I hover over the "Reporting" button
		When I click "SLA"
		Then I should see "Saved reports"
		And "Saved reports" should have option "saved test report"
		When I select "saved test report"
		Then "Selected hostgroups" should have option "LinuxServers"
		When I click "Delete"
		# Test available first, to force capybara to wait for page reload
		Then "Available hostgroups" should have option "LinuxServers"
		And "Saved reports" shouldn't have option "saved test report"
		And "Selected hostgroups" shouldn't have option "LinuxServers"