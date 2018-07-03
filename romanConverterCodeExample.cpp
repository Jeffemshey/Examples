//*************************************************************************
//Name: Jeff Emshey
//
//File Name: romanNumeralConverter.cpp
//
//Purpose: This program will provide a menu system for the user to navigate
//			through and properly convert arabic numbers of their choice to
//			roman numerals and vice-versa.
//**************************************************************************

#include<iostream>
#include<array>
#include<string>
using namespace std;

int main()
{
	//constants that will be used for set array sizes
	const int MAXSIZE_ROMAN = 14;
	const int MAXSIZE_ARABIC = 4;
	//a constant that will be used to reduce the value of a character to get its integer value
	const int reduceAscii = 48;
	//arrays that will be used to store and convert user input into useable output values
	int romanToNum[MAXSIZE_ROMAN] = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0};
	int numToRom[MAXSIZE_ARABIC] = {0, 0, 0, 0};
	//enumeration to nicely define values that will be used to swap values
	enum romanNum { I = 1, V = 5, X = 10, L = 50, C = 100, D = 500, M = 1000 };
	//strings that will be used to store and convert user input into useable output values
	string inputValue;
	string outputValueR;
	//an int that will be used for the total arabic value in the final output
	int outputValueA = 0;
	//a character that will get the user input to navigate the menu
	char getDecision;
	//flags to check if values are valid and to keep the menu loop going, respectively
	bool isValid = true;
	bool continuum = true;

	//always true, unless broken by the user
	while(continuum)
	{
		//clears any data sitting in the stream if the user tried to spam inputs in individual cases
		cin.sync();

		//get input for what function the use would like to do
		cout << "What would you like to translate? Please type 'A' for Arabic, 'R' for Roman, or 'X' to exit. Please keep all input in caps: ";
		cin >> getDecision;
		cout << endl;
	
		//clear the stream if the user tries to spam inputs.
		if(cin.peek() != '\n')
		{
			while(cin.get() != '\n')
				continue;
		}

		switch(getDecision)
		{
			//Roman Numerals to Arabic
			case 'R':
			{
				//reset values of the variables used in this case so they don't carry over from previous attempts
				outputValueA = 0;
				isValid = true;
		
				//reset loop, set all values in romanToNum to 0
				for(int resetR = 0; resetR < MAXSIZE_ROMAN; resetR++)
					romanToNum[resetR] = 0;

				//get input
				cout << "Please enter a value in Roman Numerals between M-MMM (1000-3000): ";
				cin >> inputValue;
		
				//debug check, checks to see if the size of the input string is within the possible bounds
				if(static_cast<int>(inputValue.size()) > MAXSIZE_ROMAN)
				{
					cout << "You entered too many characters to be a valid number between 1000 - 3000. " << endl << endl;
					isValid = false;
					break;
				}

				//loop to pick each character out of inputValue and place its numeral equivalent into its respective romanToNum location
				for(int inputIterator = 0; inputIterator < static_cast<int>(inputValue.size()); inputIterator++)
				{
					//debug check, verifies the first value entered is 'M' (1000) to keep within the set bounds (1000-3000)
					if(inputValue[0] != 'M')
					{
						cout << "Sorry, but the set of characters you entered must start with M (1000). " << endl << endl;
						isValid = false;
						break;
					}
					//this check prevents the user from being able to use spaces and continue writing gibberish that will carry over in the stream
					if(inputIterator == (static_cast<int>(inputValue.size()) - 1) && cin.peek() != '\n')
					{
						cout << "You cannot enter a space or some type of endline and continue to spam characters. " << endl << endl;
						isValid = false;
						break;
					}
		
					/*switch that sets the romanToNum position value equal to its Roman Numeral letter
					  via the enumerations defined in the initialization. This method will not do the math. Instead
					  if subtraction is going to take place the lower value will be set to its negative inverse and the next
					  value will remain the same. This avoids having to pull or arrange values in the romanToNum array.*/
					switch(inputValue[inputIterator])
					{
						case 'M':
							romanToNum[inputIterator] = M;
							break;
						case 'D':
							romanToNum[inputIterator] = D;
							break;
						case 'C':
							//this check makes sure that program can't look past the size of the array.
							if(inputIterator + 1 < static_cast<int>(inputValue.size()) && (inputValue[inputIterator + 1] == 'M' ||
								inputValue[inputIterator + 1] == 'D'))

								romanToNum[inputIterator] = -C;
							else
								romanToNum[inputIterator] = C;
							break;
						case 'L':
							romanToNum[inputIterator] = L;
							break;
						case 'X':
							//this check makes sure that program can't look past the size of the array.
							if(inputIterator + 1 < static_cast<int>(inputValue.size()) && (inputValue[inputIterator + 1] == 'C' ||
								inputValue[inputIterator + 1] == 'L'))

								romanToNum[inputIterator] = -X;
							else
								romanToNum[inputIterator] = X;
							break;
						case 'V':
							romanToNum[inputIterator] = V;
							break;
						case 'I':
							//this check makes sure that program can't look past the size of the array.
							if(inputIterator + 1 < static_cast<int>(inputValue.size()) && (inputValue[inputIterator + 1] == 'X' ||
								inputValue[inputIterator + 1] == 'V'))

								romanToNum[inputIterator] = -I;
							else
								romanToNum[inputIterator] = I;
							break;

						//default case if the user enters an invalid character
						default:
						{
							inputValue.clear();
							cout << "Sorry, but your input was invalid " << endl << endl;
							isValid = false;
							break;
						} 
					}//end switch 
				}//end loop

				/*this loop is done for the majority of debug checks required for the program to function accurately. The counter adder
				  is also used for the outputValueA to do its final math if the input is valid, as checked by the isValid flag. */
				for(int adder = 0; adder < MAXSIZE_ROMAN; adder++)
				{
					//this check is to define one of the core rules for roman numerals. (1*10^x) cannot be repeated more than 3x in roman numerals
					if(romanToNum[adder] == romanToNum[adder+1] && romanToNum[adder] == romanToNum[adder+2] && romanToNum[adder] == romanToNum[adder+3]
					   && (romanToNum[adder] == 1 || romanToNum[adder] == 10 || romanToNum[adder] == 100 || romanToNum[adder] == 1000))
					{
						cout << "Invalid input. A roman numeral cannot repeat a character more than 3 time consecutively. " << endl << endl;
						isValid = false;
						break;
					}

					//this check defines the second half of the first rule. (5 * 10^x) cannot be repeated at all in roman numerals.
					else if ((romanToNum[adder] == 5 || romanToNum[adder] == 50 || romanToNum[adder] == 500) &&
							  (romanToNum[adder] == romanToNum[adder+1] || romanToNum[adder] == romanToNum[adder+2] ||
							  romanToNum[adder] == romanToNum[adder+3]) && romanToNum[adder+1] != 0)
					{
						cout << "Invalid input. A roman numeral that is a factor of 5 cannot be repeated. " << endl << endl;
						isValid = false;
						break;
					}

					//this check makes sure the user can't minus a value then have a larger value appear after.
					else if(romanToNum[adder + 1] < 0 && romanToNum[adder+1] >= -romanToNum[adder + 3] && romanToNum[adder+3] != 0)
					{
						cout << "Order invalid. " << endl << endl;
						isValid = false;
						break;
					}
				
					//This check makes sure the user can't enter a smaller value then have a minus value that is larger appear after
					else if(romanToNum[adder] > 0 && romanToNum[adder] < (romanToNum[adder+1] + romanToNum[adder+2]) && romanToNum[adder+1] < 0)
					{
						cout << "Order invalid. " << endl << endl;
						isValid = false;
						break;
					}

					//this check makes sure the user can't minus the same value repeatedly.
					else if(romanToNum[adder] < 0 && romanToNum[adder] == romanToNum[adder+2] )
					{
						cout << "Order invalid. " << endl << endl;
						isValid = false;
						break;
					}

					//this check makes sure that the user can't enter two values that can't be subtracted, if left side is a smaller value.
					else if(romanToNum[adder] > 0 && romanToNum[adder] < romanToNum[adder + 1])
					{
						cout << "Order invalid. " << endl << endl;
						isValid = false;
						break;
					}

					else
					{
						//add the values that made it this far, and check if the total is over 3000
						outputValueA += romanToNum[adder];
						if(outputValueA > 3000)
						{
							cout << "Sorry, the number you entered was larger than 3000. " << endl << endl;
							isValid = false;
							break;
						}
					}//end debugging checks
				}//end loop

				//print the final outcome
				if(isValid == true)
					cout << outputValueA << endl << endl;
			}//end case 'R'
				break;

		//case that will convert Arabic into Roman Numerals
		case 'A':
		{
			//clears the string that will output the values to ensure there is no carry over from previous attempts
			outputValueR.clear();

			//reset loop, resets the array that will be used to store the arabic values
			for(int resetA = 0; resetA < MAXSIZE_ARABIC; resetA++)
				numToRom[resetA] = 0;
			
			//Reset isValid
			isValid = true;

			//get input from user, intake value as a string
			cout << "Please enter a value in Arabic between 1000-3000: ";
			cin >> inputValue;

			//debug check, a loop that will be used to check for our set parameters
			for(int checkA = 0; checkA < static_cast<int>(inputValue.size()); checkA++)
			{
				//checks to make sure the user only entered 4 values and doesn't exceed the array limit
				if(inputValue.size() != MAXSIZE_ARABIC)
				{
					cout << "Sorry, you entered too many or too few characters to be a valid number. " << endl << endl;
					isValid = false;
					break;
				}

				//math used to determine the integer value of the entered character '1', '2', '3',...,'9' and store it into numToRom
				numToRom[checkA] = inputValue[checkA] - reduceAscii;

				//check to make sure that the user has entered valid number characters based on their Ascii values
				if(inputValue[checkA] < 48 || inputValue[checkA] > 57)
				{
					cout << "The number you entered was not valid. " << endl << endl;
					isValid = false;
					break;
				}

				//checks to make sure the first value isn't below 1, as given the position (1*10^3) if its < 1, then it is less than 1000.
				else if(numToRom[0] < 1)
				{
					cout << "The number you entereted was less than 1000. Please enter a number between 100-3000. " << endl << endl;
					isValid = false;
					break;
				}

				//checks the sequential values following the first to ensure the total don't exceed 3000, and the first value isn't > 3
				else if((numToRom[0] == 3 && numToRom[checkA] != 0 && checkA != 0) || numToRom[0] > 3)
				{
					cout << "The number you entered was greater than 3000. Please enter a number between 1000-3000. " << endl << endl;
					isValid = false;
					break;
				}
			}//end initial check loop

			//the core math process if the number was valid
			if(isValid == true)
			{
				//a loop that will be used to define all conversion parameters for the value the users enters, and will build the output string
				for(int inputIteratorA = 0; inputIteratorA < MAXSIZE_ARABIC; inputIteratorA++)
				{
					//checks to see if the values are 1-3 or 6-8 and will add the the respective character to the output string
					if(numToRom[inputIteratorA] <= 3 || numToRom[inputIteratorA] >= 6 && numToRom[inputIteratorA] <= 8)
					{
						if(numToRom[inputIteratorA] >= 6)
						{
							/*if the value is greater than or equal to 6, these characters must be added to the string first
							  before the smaller values are added*/
							if(inputIteratorA == 1)
								outputValueR += 'D';
							if(inputIteratorA == 2)
								outputValueR += 'L';
							if(inputIteratorA == 3)
								outputValueR += 'V';

							/*sets the values of 6, 7, and 8 equal to 1, 2, 3, so they can utilize the same output values,
							  since their structure is the same*/
							numToRom[inputIteratorA] = (numToRom[inputIteratorA] % 3) + 1;
						}

						/*if the value of position inputIterator is 1..3 this loop will cout upwards to that value
						  to add the proper amount of repeating characters*/
						for(int faceValue = 1; faceValue <= numToRom[inputIteratorA]; faceValue++)
						{
							if(inputIteratorA == 0)
								outputValueR += 'M';
							if(inputIteratorA == 1)
								outputValueR += 'C';
							if(inputIteratorA == 2)
								outputValueR += 'X';
							if(inputIteratorA == 3)
								outputValueR += 'I';
						}
					}

					//checks if a value is 4 and adds the proper characters to the output string
					else if(numToRom[inputIteratorA] == 4)
					{
						if(inputIteratorA == 1)
							outputValueR += "CD";
						if(inputIteratorA == 2)
							outputValueR += "XL";
						if(inputIteratorA == 3)
							outputValueR += "IV";
					}
					//checks if any values are equal to 5, adds the appropiate characters to the output string
					if(numToRom[inputIteratorA] == 5)
					{
						if(inputIteratorA == 1)
							outputValueR += 'D';
						if(inputIteratorA == 2)
							outputValueR += 'L';
						if(inputIteratorA == 3)
							outputValueR += 'V';
					}

					//checks if any values are equal to 9, adds the appropiate characters to the output string
					else if(numToRom[inputIteratorA] == 9)
					{
						if(inputIteratorA == 1)
							outputValueR += "CM";
						if(inputIteratorA == 2)
							outputValueR += "XC";
						if(inputIteratorA == 3)
							outputValueR += "IX";
					}
				
				}//endloop
				//final output
				cout << "Your value in Roman Numerals is: " << outputValueR << endl << endl;
			}//end isValid
			}//end case a
			break;

		//case that will set the continuum flag to false and will break the loop if the user enters 'X' to exit
		case 'X':
			continuum = false;
			break;

		//default case that will be used if the user enters an invalid character
		default:
			cout << "Sorry, but the character you entered was invalid. " << endl << endl;
		}//end of the getdecision switch
	}//end of the continuum loop

	return 0;
}